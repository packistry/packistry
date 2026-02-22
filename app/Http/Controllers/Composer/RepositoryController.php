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
use App\Models\DeployToken;
use App\Models\Package;
use App\Models\Scopes\PackageTokenAccessScope;
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
        $repository = $this->repository();
        $token = $this->token();

        $packagesQuery = Package::query()
            ->orderBy('name')
            ->when($q, fn (BuilderContract $query) => $query
                ->where('name', 'like', "$q%"))
            ->when($type, fn (BuilderContract $query) => $query
                ->where('type', "$type"));

        new PackageTokenAccessScope(token: $token, repository: $repository)
            ->apply($packagesQuery);

        $packages = $packagesQuery->get();

        $results = $packages->map(fn (Package $package): array => [
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

        $packagesQuery = Package::query();

        new PackageTokenAccessScope(token: $token, repository: $repository)
            ->apply($packagesQuery);

        $names = $packagesQuery
            ->orderBy('name')
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

        $repository = $this->repository();

        $packageQuery = $repository
            ->packages()
            ->where('name', "$vendor/$name")
            ->with([
                'versions' => fn (BuilderContract $query) => $query
                    ->where('name', 'not like', 'dev-%')
                    ->where('name', 'not like', '%-dev'),
            ]);

        new PackageTokenAccessScope(token: $this->token(), repository: $repository)
            ->apply($packageQuery->getQuery());

        /** @var Package $package */
        $package = $packageQuery->firstOrFail();

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

        $repository = $this->repository();

        $packageQuery = $repository
            ->packages()
            ->where('name', "$vendor/$name")
            ->with([
                'versions' => fn (BuilderContract $query) => $query
                    ->where('name', 'like', 'dev-%')
                    ->orWhere('name', 'like', '%-dev'),
            ]);

        new PackageTokenAccessScope(token: $this->token(), repository: $repository)
            ->apply($packageQuery->getQuery());

        /** @var Package $package */
        $package = $packageQuery->firstOrFail();

        $package->setRelation('repository', $repository);

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
        $packageName = "$vendor/$name";

        $packageQuery = $repository
            ->packages()
            ->where('name', $packageName);

        new PackageTokenAccessScope(token: $this->token(), repository: $repository)
            ->apply($packageQuery->getQuery());

        /** @var Package $package */
        $package = $packageQuery->firstOrFail();

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

        $repository = $this->repository();
        $token = $this->token();
        $packageName = "$vendor/$name";

        /** @var Package|null $package */
        $package = $repository
            ->packages()
            ->where('name', $packageName)
            ->first();

        if ($token instanceof DeployToken
            && ! $token->accessibleRepositoryIdsQuery()->where('repositories.id', $repository->id)->exists()
        ) {
            if ($package === null || ! $token->hasAccessToPackage($package)) {
                abort(404);
            }
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:zip'],
            'version' => ['string'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        if (is_null($package)) {
            $package = new Package;
            $package->repository_id = $repository->id;
            $package->type = PackageType::LIBRARY->value;
            $package->name = $packageName;

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
