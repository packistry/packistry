<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Ability;
use App\Enums\PackageType;
use App\Models\Package;
use App\Models\Repository;
use App\Models\User;
use App\Models\Version;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ComposerRepositoryController extends Controller
{
    public function user(): ?User
    {
        /** @var User $user */
        $user = Auth::guard('sanctum')->user();

        return $user;
    }

    public function repository(): Repository
    {
        return once(function () {
            $name = request()->route('repository');

            return Repository::query()
                ->when(
                    $name,
                    fn (BuilderContract $query) => $query->where('name', $name),
                    fn (BuilderContract $query) => $query->whereNull('name')
                )
                ->firstOrFail();
        });
    }

    private function authorize(Ability $ability): void
    {
        $user = $this->user();

        if (is_null($user) && $this->repository()->public && in_array($ability, Ability::readAbilities())) {
            return;
        }

        if (is_null($user) || ! $user->tokenCan($ability->value)) {
            abort(401);
        }
    }

    public function packages(Request $request): JsonResponse
    {
        $this->authorize(Ability::REPOSITORY_READ);
        $base = $this->repository()->name.'/';

        return response()->json([
            'search' => url("{$base}search.json?q=%query%&type=%type%"),
            'metadata-url' => url("{$base}p2/%package%.json"),
            'list' => url("{$base}list.json"),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize(Ability::REPOSITORY_READ);

        $q = $request->input('q');
        $type = $request->input('type');

        $packagesQuery = $this->repository()
            ->packages()
            ->when($q, fn (BuilderContract $query) => $query
                ->where('name', 'like', "$q%"))
            ->when($type, fn (BuilderContract $query) => $query
                ->where('type', "$type"));

        return response()->json([
            'total' => $packagesQuery->count(),
            'results' => $packagesQuery->chunkMap(fn (Package $package): array => [
                'name' => $package->name,
                'description' => '',
                'downloads' => 0,
            ]),
        ]);
    }

    public function list(): JsonResponse
    {
        $this->authorize(Ability::REPOSITORY_READ);

        $names = $this->repository()
            ->packages()
            ->pluck('name');

        return response()->json([
            'packageNames' => $names,
        ]);
    }

    public function package(Request $request): JsonResponse
    {
        $this->authorize(Ability::REPOSITORY_READ);

        $vendor = $request->route('vendor');
        $name = $request->route('name');

        if (! is_string($vendor) || ! is_string($name)) {
            abort(404);
        }

        /** @var Package $package */
        $package = $this
            ->repository()
            ->packages()
            ->where('name', "$vendor/$name")
            ->firstOrFail();

        return response()->json([
            'minified' => 'composer/2.0',
            'packages' => [
                $package->name => $package->versions->map(fn (Version $version) => [
                    ...$version->metadata,
                    'name' => $package->name,
                    'version' => $version->name,
                    'type' => 'library',
                    'time' => $version->created_at,
                    'dist' => [
                        'type' => 'zip',
                        'url' => url("$package->name/$version->name"),
                        'shasum' => $version->shasum,
                    ],
                ]),
            ],
        ]);
    }

    public function packageDev(Request $request): JsonResponse
    {
        return $this->package($request);
    }

    public function download(Request $request): string
    {
        $this->authorize(Ability::REPOSITORY_READ);

        $vendor = $request->route('vendor');
        $name = $request->route('name');
        $version = $request->route('version');

        if (! is_string($vendor) || ! is_string($name) || ! is_string($version)) {
            abort(404);
        }

        $content = Storage::get("$vendor-$name-$version.zip");

        if (is_null($content)) {
            abort(404);
        }

        return $content;
    }

    public function upload(Request $request): JsonResponse
    {
        $this->authorize(Ability::REPOSITORY_WRITE);

        $vendor = $request->route('vendor');
        $name = $request->route('name');

        if (! is_string($vendor) || ! is_string($name)) {
            abort(404);
        }

        /** @var Package|null $package */
        $package = $this
            ->repository()
            ->packages()
            ->where('name', "$vendor/$name")
            ->first();

        try {
            $request->validate([
                'file' => ['required', 'file', 'mimes:zip'],
                'version' => ['string'],
            ]);
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        }

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $content = @file_get_contents("zip://{$file->getRealPath()}#composer.json");

        if ($content === false) {
            return response()->json([
                'file' => ['composer.json not found in archive'],
            ], 422);
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $version = $request->input('version', $decoded['version'] ?? null);

        if ($version === null) {
            return response()->json([
                'version' => ['no version provided'],
            ], 422);
        }

        $package ??= new Package;

        if (! $package->exists) {
            $package->name = "$vendor/$name";
            $package->type = array_key_exists('type', $decoded)
                ? PackageType::tryFrom($decoded['type']) ?? PackageType::LIBRARY
                : PackageType::LIBRARY;

            $this->repository()->packages()->save($package);
            $package->save();
        }

        $archiveName = "$vendor-$name-$version.zip";

        $newVersion = new Version;

        $newVersion->package_id = $package->id;
        $newVersion->name = $version;
        $newVersion->shasum = hash('sha1', $file->getContent());
        $newVersion->metadata = collect($decoded)->only([
            'description',
            'readme',
            'keywords',
            'homepage',
            'license',
            'authors',
            'bin',
            'autoload',
            'autoload-dev',
            'extra',
            'require',
            'require-dev',
            'suggest',
            'provide',
        ])->toArray();

        $newVersion->save();

        $file->storeAs($archiveName);

        return response()->json($newVersion, 201);
    }
}
