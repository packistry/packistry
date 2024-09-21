<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ComposerRepositoryController extends Controller
{
    public function repository(): Repository
    {
        $name = request()->route('repository');

        return Repository::query()
            ->where('name', $name)
            ->firstOrFail();
    }

    public function packages(): JsonResponse
    {
        return response()->json([
            'search' => url('/search.json?q=%query%&type=%type%'),
            'metadata-url' => url('/p2/%package%.json'),
            'list' => url('/list.json'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');

        $packagesQuery = $this->repository()
            ->packages()
            ->where('name', 'like', "$query%");

        return response()->json([
            'total' => $packagesQuery->count(),
            'results' => $packagesQuery->chunkMap(fn (Package $package) => [
                'name' => $package->name,
                'description' => '',
                'downloads' => 0,
            ]),
        ]);
    }

    public function list(): JsonResponse
    {
        $names = $this->repository()
            ->packages()
            ->pluck('name');

        return response()->json([
            'packageNames' => $names,
        ]);
    }

    public function package(string $vendor, string $name): JsonResponse
    {
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

    public function packageDev(string $vendor, string $name): JsonResponse
    {
        // @todo fix dev
        return $this->package($vendor, $name);
    }

    public function download(string $vendor, string $name, string $version): string
    {
        $content = Storage::get("$vendor-$name-$version.zip");

        if (is_null($content)) {
            abort(404);
        }

        return $content;
    }

    public function upload(Request $request, string $vendor, string $name): JsonResponse
    {
        /** @var Package|null $package */
        $package = $this
            ->repository()
            ->packages()
            ->where('name', "$vendor/$name")
            ->first();

        if (is_null($package)) {
            return response()->json(['error' => 'package not found'], 404);
        }

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
