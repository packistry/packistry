<?php

declare(strict_types=1);

namespace App\Mirror;

use App\Enums\PackageType;
use App\Exceptions\UnsupportedUpstreamException;
use App\Exceptions\UpstreamUnavailableException;
use App\Jobs\RevalidateMetadata;
use App\Models\MirrorMetadata;
use App\Models\Package;
use App\Models\Upstream;
use Composer\Semver\VersionParser;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

/**
 * Caches upstream package metadata (p2 files). Cached metadata is revalidated after the
 * configured ttl and keeps being served when the upstream is unavailable. Within the stale ttl
 * after that, it is served right away and revalidated by a queued job.
 */
readonly class MetadataCache
{
    public function __construct(private UpstreamClient $client)
    {
        //
    }

    public function get(Upstream $upstream, string $name, bool $dev = false): MetadataResult
    {
        $cached = $this->cached($upstream, $name, $dev);

        if ($cached instanceof MirrorMetadata && $this->isFresh($cached)) {
            return $this->load($cached) ?? $this->fetchLocked($upstream, $name, $dev);
        }

        if (! $cached instanceof MirrorMetadata && Cache::has($this->notFoundKey($upstream, $name, $dev))) {
            return MetadataResult::notFound();
        }

        if ($this->client->isDown($upstream)) {
            return $this->stale($cached);
        }

        if ($cached instanceof MirrorMetadata && $this->isServableWhileRevalidating($cached)) {
            $result = $this->load($cached);

            if ($result instanceof MetadataResult) {
                RevalidateMetadata::dispatch($upstream, $name, $dev);

                return $result;
            }
        }

        return $this->fetchLocked($upstream, $name, $dev);
    }

    /**
     * Fetch the metadata from the upstream now, regardless of its cache state.
     */
    public function refresh(Upstream $upstream, string $name, bool $dev = false): MetadataResult
    {
        Cache::forget($this->notFoundKey($upstream, $name, $dev));
        Cache::forget("mirror:down:$upstream->id");

        $cached = $this->cached($upstream, $name, $dev);

        if ($cached instanceof MirrorMetadata) {
            $cached->checked_at = null;
            $cached->save();
        }

        return $this->fetchLocked($upstream, $name, $dev);
    }

    /**
     * Revalidate expired metadata, unless another process is already fetching it.
     */
    public function revalidate(Upstream $upstream, string $name, bool $dev): void
    {
        Cache::lock($this->lockKey($upstream, $name, $dev), 30)
            ->get(function () use ($upstream, $name, $dev): void {
                $cached = $this->cached($upstream, $name, $dev);

                if ($cached instanceof MirrorMetadata && $this->isFresh($cached)) {
                    return;
                }

                $this->fetch($upstream, $name, $dev, $cached);
            });
    }

    public function forget(Upstream $upstream, string $name): void
    {
        foreach ([false, true] as $dev) {
            Cache::forget($this->notFoundKey($upstream, $name, $dev));

            $cached = $this->cached($upstream, $name, $dev);

            if ($cached instanceof MirrorMetadata) {
                Storage::delete($cached->body_path);
                $cached->delete();
            }
        }
    }

    private function fetchLocked(Upstream $upstream, string $name, bool $dev): MetadataResult
    {
        try {
            return Cache::lock($this->lockKey($upstream, $name, $dev), 30)
                ->block(15, function () use ($upstream, $name, $dev): MetadataResult {
                    // another request may have refreshed it while we waited for the lock
                    $cached = $this->cached($upstream, $name, $dev);

                    if ($cached instanceof MirrorMetadata && $this->isFresh($cached)) {
                        $result = $this->load($cached);

                        if ($result instanceof MetadataResult) {
                            return $result;
                        }
                    }

                    return $this->fetch($upstream, $name, $dev, $cached);
                });
        } catch (LockTimeoutException) {
            return $this->stale($this->cached($upstream, $name, $dev));
        }
    }

    private function fetch(Upstream $upstream, string $name, bool $dev, ?MirrorMetadata $cached): MetadataResult
    {
        try {
            $url = str_replace(
                '%package%',
                $dev ? "$name~dev" : $name,
                $this->client->metadataUrl($upstream)
            );

            $request = $this->client->request($upstream)->acceptJson();

            if ($cached?->last_modified !== null && $this->load($cached) instanceof MetadataResult) {
                $request = $request->withHeaders(['If-Modified-Since' => $cached->last_modified]);
            }

            $response = $request->get($url);
        } catch (ConnectionException $e) {
            $this->client->markDown($upstream, $e->getMessage());

            return $this->stale($cached);
        } catch (UpstreamUnavailableException|UnsupportedUpstreamException) {
            return $this->stale($cached);
        }

        if ($response->status() === 304 && $cached instanceof MirrorMetadata) {
            $this->client->recordSuccess($upstream);

            $cached->checked_at = now();
            $cached->save();

            // revalidated, so not stale
            return $this->load($cached) ?? MetadataResult::unavailable();
        }

        if ($response->status() === 404 && $cached instanceof MirrorMetadata && ! $this->rootAnswers($upstream)) {
            return $this->stale($cached);
        }

        if ($response->status() === 404) {
            $this->client->recordSuccess($upstream);

            if ($cached instanceof MirrorMetadata) {
                Storage::delete($cached->body_path);
                $cached->delete();
            }

            Cache::put($this->notFoundKey($upstream, $name, $dev), true, config()->integer('mirror.not_found_ttl'));

            return MetadataResult::notFound();
        }

        if (! $response->successful()) {
            $error = "Fetching $url failed with HTTP {$response->status()}";

            $response->serverError()
                ? $this->client->markDown($upstream, $error)
                : $this->client->recordError($upstream, $error);

            return $this->stale($cached);
        }

        $body = $response->body();

        try {
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $data = null;
        }

        if (! is_array($data) || ! is_array($data['packages'] ?? null)) {
            $this->client->recordError($upstream, "Fetching $url returned invalid metadata");

            return $this->stale($cached);
        }

        $this->client->recordSuccess($upstream);

        /** @var array<string, mixed> $data */
        $cached ??= new MirrorMetadata;
        $cached->upstream_id = $upstream->id;
        $cached->name = $name;
        $cached->dev = $dev;
        $cached->body_path = $upstream->storagePath('metadata/'.$name.($dev ? '~dev' : '').'.json.gz');

        $lastModified = $response->header('Last-Modified');
        $cached->last_modified = $lastModified === '' ? null : $lastModified;
        $cached->fetched_at = now();
        $cached->checked_at = now();

        Storage::put($cached->body_path, (string) gzencode($body));
        $cached->save();

        $result = MetadataResult::found($data, $cached->fetched_at);

        if (! $dev) {
            Package::query()
                ->where('upstream_id', $upstream->id)
                ->where('name', $name)
                ->update(self::details($result, $name));
        }

        return $result;
    }

    /**
     * Package attributes derived from the newest stable version in the metadata.
     *
     * @return array{type: string, description: string|null, latest_version: string|null}
     */
    public static function details(MetadataResult $result, string $name): array
    {
        $versions = $result->versions($name);
        $parser = new VersionParser;

        $latest = null;

        foreach ($versions as $version) {
            $versionName = $version['version'] ?? null;

            if (is_string($versionName) && $parser->parseStability($versionName) === 'stable') {
                $latest = $version;
                break;
            }
        }

        $reference = $latest ?? $versions[0] ?? [];

        $type = $reference['type'] ?? null;
        $description = $reference['description'] ?? null;
        $latestVersion = $latest['version'] ?? null;

        return [
            'type' => is_string($type) && $type !== '' ? $type : PackageType::LIBRARY->value,
            'description' => is_string($description) ? Str::limit($description, 250) : null,
            'latest_version' => is_string($latestVersion) ? $latestVersion : null,
        ];
    }

    /**
     * Some registries answer 404 instead of 401 or 403 once credentials stop working, so a 404 for a
     * cached package only means it is gone when the upstream itself still answers.
     */
    private function rootAnswers(Upstream $upstream): bool
    {
        try {
            $this->client->metadataUrl($upstream, force: true);
        } catch (UpstreamUnavailableException|UnsupportedUpstreamException) {
            return false;
        }

        return true;
    }

    private function cached(Upstream $upstream, string $name, bool $dev): ?MirrorMetadata
    {
        return MirrorMetadata::query()
            ->where('upstream_id', $upstream->id)
            ->where('name', $name)
            ->where('dev', $dev)
            ->first();
    }

    private function isFresh(MirrorMetadata $cached): bool
    {
        return $cached->checked_at?->gt(now()->subSeconds(config()->integer('mirror.metadata_ttl'))) ?? false;
    }

    private function isServableWhileRevalidating(MirrorMetadata $cached): bool
    {
        $window = config()->integer('mirror.metadata_ttl') + config()->integer('mirror.metadata_stale_ttl');

        return $cached->checked_at?->gt(now()->subSeconds($window)) ?? false;
    }

    private function stale(?MirrorMetadata $cached): MetadataResult
    {
        if (! $cached instanceof MirrorMetadata) {
            return MetadataResult::unavailable();
        }

        return $this->load($cached)?->asStale() ?? MetadataResult::unavailable();
    }

    private function load(MirrorMetadata $cached): ?MetadataResult
    {
        try {
            $contents = Storage::get($cached->body_path);
            $body = is_string($contents) ? gzdecode($contents) : false;
            $data = is_string($body) ? json_decode($body, true, flags: JSON_THROW_ON_ERROR) : null;
        } catch (Throwable) {
            $data = null;
        }

        if (! is_array($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        return MetadataResult::found($data, $cached->fetched_at);
    }

    /**
     * Includes the upstream url, so not-found answers of a previous url are not reused after it changes.
     */
    private function notFoundKey(Upstream $upstream, string $name, bool $dev): string
    {
        $url = sha1($upstream->url);

        return "mirror:not-found:$upstream->id:$url:$name:".($dev ? 'dev' : 'stable');
    }

    private function lockKey(Upstream $upstream, string $name, bool $dev): string
    {
        return "mirror:lock:metadata:$upstream->id:$name:".($dev ? 'dev' : 'stable');
    }
}
