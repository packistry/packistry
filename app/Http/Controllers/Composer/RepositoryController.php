<?php

declare(strict_types=1);

namespace App\Http\Controllers\Composer;

use App\Archive;
use App\CreateFromZip;
use App\Enums\PackageType;
use App\Enums\TokenAbility;
use App\Events\PackageDownloadEvent;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Http\Controllers\RepositoryAwareController;
use App\Http\Resources\ComposerPackageResource;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class RepositoryController extends RepositoryAwareController
{
    public function __construct(private readonly CreateFromZip $createFromZip) {}

    public function packages(): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);
        $repository = $this->repository();

        return response()->json([
            'search' => $repository->url('/search.json?q=%query%&type=%type%'),
            'metadata-url' => $repository->url('/p2/%package%.json'),
            'list' => $repository->url('/list.json'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

        $q = $request->input('q');
        $type = $request->input('type');
        $repository = $this->repository();
        $token = $this->token();

        $packagesQuery = $repository
            ->packages()
            ->orderBy('name')
            ->when($q, fn (BuilderContract $query) => $query
                ->where('name', 'like', "$q%"))
            ->when($type, fn (BuilderContract $query) => $query
                ->where('type', "$type"));

        // Filter packages based on token's package-level access
        // Use lazy() for memory efficiency with large package lists
        $results = $packagesQuery->lazy()->filter(function (Package $package) use ($repository, $token) {
            // Public repositories are accessible without authentication
            if ($repository->public) {
                return true;
            }

            // Token must have access to the specific package
            return $token?->hasAccessToPackage($package) ?? false;
        })->map(fn (Package $package): array => [
            'name' => $package->name,
            'description' => $package->description,
            'downloads' => $package->total_downloads,
        ])->values()->all();

        return response()->json([
            'total' => count($results),
            'results' => $results,
        ]);
    }

    public function list(): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

        $repository = $this->repository();
        $token = $this->token();

        // Filter package names based on token's package-level access
        // Use lazy() for memory efficiency with large package lists
        $names = $repository
            ->packages()
            ->orderBy('name')
            ->lazy()
            ->filter(function (Package $package) use ($repository, $token) {
                // Public repositories are accessible without authentication
                if ($repository->public) {
                    return true;
                }

                // Token must have access to the specific package
                return $token?->hasAccessToPackage($package) ?? false;
            })
            ->map(fn (Package $package): string => $package->name)
            ->values()
            ->all();

        return response()->json([
            'packageNames' => $names,
        ]);
    }

    public function package(Request $request): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

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
                    ->where('name', 'not like', 'dev-%')
                    ->where('name', 'not like', '%-dev'),
            ])
            ->firstOrFail();

        // Check package-level access (use 404 to avoid leaking package existence)
        $repository = $this->repository();
        $token = $this->token();
        if (! $repository->public && ($token === null || ! $token->hasAccessToPackage($package))) {
            abort(404);
        }

        $package->setRelation('repository', $repository);

        return response()->json(new ComposerPackageResource($package));
    }

    public function packageDev(Request $request): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

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
                    ->where('name', 'like', 'dev-%')
                    ->orWhere('name', 'like', '%-dev'),
            ])
            ->firstOrFail();

        // Check package-level access (use 404 to avoid leaking package existence)
        $repository = $this->repository();
        $token = $this->token();
        if (! $repository->public && ($token === null || ! $token->hasAccessToPackage($package))) {
            abort(404);
        }

        $package->setRelation('repository', $repository);

        return response()->json(new ComposerPackageResource($package));
    }

    /**
     * @throws Throwable
     */
    public function download(Request $request): Response
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

        $vendor = $request->route('vendor');
        $name = $request->route('name');
        $version = $request->route('version');

        if (! is_string($vendor) || ! is_string($name) || ! is_string($version)) {
            abort(404);
        }

        $repository = $this->repository();
        $package = $repository
            ->packageByNameOrFail("$vendor/$name");

        // Check package-level access (use 404 to avoid leaking package existence)
        $token = $this->token();
        if (! $repository->public && ($token === null || ! $token->hasAccessToPackage($package))) {
            abort(404);
        }

        $archiveName = Archive::name($package, $version);
        $content = Storage::get($archiveName);

        if (is_null($content)) {
            abort(404);
        }

        event(new PackageDownloadEvent(
            package: $package,
            version: $version,
            ip: $request->ip(),
            token: $this->token()?->currentAccessToken()
        ));

        return response($content)
            ->header('Content-Disposition', 'attachment; filename="'.$archiveName.'"')
            ->header('Content-Type', 'application/zip');
    }

    public function upload(Request $request): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_WRITE);

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
        $repository = $this->repository();
        $package = $repository
            ->packages()
            ->where('name', "$vendor/$name")
            ->first();

        if (is_null($package)) {
            $package = new Package;
            $package->repository_id = $repository->id;
            $package->type = PackageType::LIBRARY->value;
            $package->name = "$vendor/$name";

            $package->save();
        }

        // Check package-level access (use 404 to avoid leaking package existence)
        $token = $this->token();
        if (! $repository->public && ($token === null || ! $token->hasAccessToPackage($package))) {
            abort(404);
        }

        try {
            $version = $this->createFromZip->create(
                package: $package,
                path: $file->getRealPath(),
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
        } catch (NameNotFoundException) {
            return response()->json([
                'name' => ['no name provided'],
            ], 422);
        } catch (FailedToOpenArchiveException) {
            return response()->json([
                'archive' => ['failed to open archive'],
            ], 422);
        }

        return response()->json(new VersionResource($version), 201);
    }
}
