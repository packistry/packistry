<?php

declare(strict_types=1);

namespace App\Http\Controllers\Composer;

use App\Enums\MirrorDownloadStats;
use App\Enums\MirrorStatus;
use App\Enums\TokenAbility;
use App\Events\PackageDownloadEvent;
use App\Exceptions\UpstreamUnavailableException;
use App\Http\Controllers\RepositoryAwareController;
use App\Mirror\DistCache;
use App\Models\MirrorDist;
use App\Models\Package;
use App\Models\Upstream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MirrorController extends RepositoryAwareController
{
    public function __construct(private readonly DistCache $dists) {}

    public function download(Request $request): StreamedResponse
    {
        $this->authorize(TokenAbility::REPOSITORY_READ);

        $vendor = $request->route('vendor');
        $name = $request->route('name');
        $reference = $request->route('reference');

        if (! is_string($vendor) || ! is_string($name) || ! is_string($reference)) {
            abort(404);
        }

        $repository = $this->repository();

        /** @var Package|null $package */
        $package = $repository->packages()
            ->where('name', "$vendor/$name")
            ->where('mirror_status', MirrorStatus::OK->value)
            ->first();

        if ($package === null || ! $this->tokenCanAccessPackage($package)) {
            abort(404);
        }

        /** @var Upstream $upstream */
        $upstream = Upstream::query()
            ->with('credentials')
            ->findOrFail($package->upstream_id);

        try {
            $dist = $this->dists->get($upstream, $package->name, $reference);
        } catch (UpstreamUnavailableException) {
            abort(502);
        }

        if (! $dist instanceof MirrorDist) {
            abort(404);
        }

        $this->recordDownload($request, $package, $dist);

        return Storage::download($dist->archive_path, basename($dist->archive_path));
    }

    private function recordDownload(Request $request, Package $package, MirrorDist $dist): void
    {
        $stats = MirrorDownloadStats::tryFrom(config()->string('mirror.download_stats')) ?? MirrorDownloadStats::FULL;

        match ($stats) {
            MirrorDownloadStats::FULL => event(new PackageDownloadEvent(
                package: $package,
                version: null,
                ip: $request->ip(),
                token: $this->token()?->currentAccessToken(),
                versionName: $dist->version,
            )),
            MirrorDownloadStats::COUNTS => Package::query()->whereKey($package->id)->increment('total_downloads'),
            MirrorDownloadStats::OFF => null,
        };
    }
}
