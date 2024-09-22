<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\CreateFromZip;
use App\Enums\Ability;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ComposerRepositoryController extends Controller
{
    public function __construct(private readonly CreateFromZip $createFromZip) {}

    public function packages(): JsonResponse
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
            ->with([
                'versions' => fn (BuilderContract $query) => $query
                    ->where('name', 'not like', 'dev-%'),
            ])
            ->firstOrFail();

        return response()->json(new PackageResource($package));
    }

    public function packageDev(Request $request): JsonResponse
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
            ->with([
                'versions' => fn (BuilderContract $query) => $query
                    ->where('name', 'like', 'dev-%'),
            ])
            ->firstOrFail();

        return response()->json(new PackageResource($package));
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

        try {
            $version = $this->createFromZip->create(
                repository: $this->repository(),
                path: $file->getRealPath(),
                name: "$vendor/$name",
                version: $request->input('version')
            );
        } catch (ComposerJsonNotFoundException) {
            return response()->json([
                'file' => ['composer.json not found in archive'],
            ], 422);
        } catch (VersionNotFoundException) {
            return response()->json([
                'version' => ['no version provided'],
            ], 422);
        }

        return response()->json($version, 201);
    }
}
