<?php

declare(strict_types=1);

namespace App\Http\Controllers\Composer;

use App\CreateFromZip;
use App\Enums\PackageType;
use App\Enums\TokenAbility;
use App\Events\PackageDownloadEvent;
use App\Http\Controllers\RepositoryAwareController;
use App\Http\Resources\ComposerPackageResource;
use App\Http\Resources\VersionResource;
use App\Models\Package;
use App\Models\Version;
use App\Normalizer;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

        $packagesQuery = $this->repository()
            ->packages()
            ->orderBy('name')
            ->when($q, fn (BuilderContract $query) => $query
                ->where('name', 'like', "$q%"))
            ->when($type, fn (BuilderContract $query) => $query
                ->where('type', "$type"));

        return response()->json([
            'total' => $packagesQuery->count(),
            'results' => $packagesQuery->chunkMap(fn (Package $package): array => [
                'name' => $package->name,
                'description' => $package->description,
                'downloads' => $package->total_downloads,
            ]),
        ]);
    }

    public function list(): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

        $names = $this->repository()
            ->packages()
            ->pluck('name');

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

        $package->setRelation('repository', $this->repository());

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

        $package->setRelation('repository', $this->repository());

        return response()->json(new ComposerPackageResource($package));
    }

    /**
     * @throws Throwable
     */
    public function download(Request $request): StreamedResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

        $vendor = $request->route('vendor');
        $name = $request->route('name');
        $versionName = $request->route('version');

        if (! is_string($vendor) || ! is_string($name) || ! is_string($versionName)) {
            abort(404);
        }

        $repository = $this->repository();
        $package = $repository
            ->packageByNameOrFail("$vendor/$name");

        /** @var Version $version */
        $version = $package
            ->versions()
            ->where('name', Normalizer::version($versionName))
            ->firstOrFail();

        if ($version->archive_path === null || ! Storage::exists($version->archive_path)) {
            abort(404);
        }

        event(new PackageDownloadEvent(
            package: $package,
            version: $version,
            ip: $request->ip(),
            token: $this->token()?->currentAccessToken()
        ));

        return Storage::download($version->archive_path);
    }

    public function upload(Request $request): JsonResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_WRITE);

        $vendor = $request->route('vendor');
        $name = $request->route('name');

        if (! is_string($vendor) || ! is_string($name)) {
            abort(404);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:zip'],
            'version' => ['string'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $package = $this->repository()
            ->packages()
            ->where('name', "$vendor/$name")
            ->first();

        if (is_null($package)) {
            $package = new Package;
            $package->repository_id = $this->repository()->id;
            $package->type = PackageType::LIBRARY->value;
            $package->name = "$vendor/$name";

            $package->save();
        }

        $version = $this->createFromZip->create(
            package: $package,
            path: $file->getRealPath(),
            version: $request->input('version')
        );

        return response()->json(new VersionResource($version), 201);
    }
}
