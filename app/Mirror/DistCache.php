<?php

declare(strict_types=1);

namespace App\Mirror;

use App\Exceptions\UpstreamUnavailableException;
use App\Models\MirrorDist;
use App\Models\Upstream;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Downloads each upstream dist once and serves the stored copy afterwards.
 */
readonly class DistCache
{
    public function __construct(
        private UpstreamClient $client,
        private MetadataCache $metadata,
    ) {
        //
    }

    /**
     * The key identifying a dist in Packistry urls and storage.
     *
     * @param  array<array-key, mixed>  $dist
     */
    public static function key(array $dist): string
    {
        $reference = $dist['reference'] ?? null;

        if (is_string($reference) && preg_match('{^[A-Za-z0-9._-]{1,100}$}', $reference) === 1) {
            return $reference;
        }

        $url = $dist['url'] ?? '';

        return sha1(is_string($reference) && $reference !== '' ? $reference : (is_string($url) ? $url : ''));
    }

    /**
     * @throws UpstreamUnavailableException
     */
    public function get(Upstream $upstream, string $name, string $key): ?MirrorDist
    {
        $stored = $this->stored($upstream, $name, $key);

        if ($stored instanceof MirrorDist) {
            return $this->touch($stored);
        }

        try {
            return Cache::lock("mirror:lock:dist:$upstream->id:$name:$key", config()->integer('mirror.download_timeout') + 30)
                ->block(config()->integer('mirror.download_timeout'), fn (): ?MirrorDist => $this->stored($upstream, $name, $key) ?? $this->download($upstream, $name, $key));
        } catch (LockTimeoutException) {
            throw new UpstreamUnavailableException("Timed out waiting for download of $name ($key)");
        }
    }

    /**
     * @throws UpstreamUnavailableException
     */
    private function download(Upstream $upstream, string $name, string $key): ?MirrorDist
    {
        $found = $this->find($upstream, $name, $key);

        if ($found === null) {
            return null;
        }

        [$dist, $version] = $found;

        /** @var string $url */
        $url = $dist['url'];
        $type = is_string($dist['type'] ?? null) ? $dist['type'] : 'zip';

        $temp = tempnam(sys_get_temp_dir(), 'packistry-mirror-');

        if ($temp === false) {
            throw new RuntimeException('Failed to create temporary file');
        }

        try {
            try {
                $response = $this->client
                    ->request($upstream, config()->integer('mirror.download_timeout'))
                    ->sink($temp)
                    ->get($url);
            } catch (ConnectionException $e) {
                $this->client->recordError($upstream, $e->getMessage());

                throw new UpstreamUnavailableException($e->getMessage(), $e->getCode(), previous: $e);
            }

            if (! $response->successful()) {
                $this->client->recordError($upstream, $message = "Downloading $url failed with HTTP {$response->status()}");

                throw new UpstreamUnavailableException($message);
            }

            $shasum = is_string($dist['shasum'] ?? null) && $dist['shasum'] !== '' ? $dist['shasum'] : null;
            $hash = sha1_file($temp);

            if ($shasum !== null && $hash !== $shasum) {
                $this->client->recordError($upstream, $message = "Downloaded dist of $name ($key) does not match shasum $shasum");

                throw new UpstreamUnavailableException($message);
            }

            if ($type === 'zip' && ! $this->isZip($temp)) {
                $this->client->recordError($upstream, $message = "Downloaded dist of $name ($key) is not a valid zip archive");

                throw new UpstreamUnavailableException($message);
            }

            $extension = (string) preg_replace('{[^a-z0-9]}', '', strtolower($type));
            $extension = $extension === '' ? 'zip' : $extension;
            $path = $upstream->storagePath("dists/$name/$key.$extension");

            $stream = fopen($temp, 'rb');

            if ($stream === false) {
                throw new RuntimeException('Failed to open downloaded dist');
            }

            try {
                Storage::writeStream($path, $stream);
            } finally {
                fclose($stream);
            }

            $stored = new MirrorDist;
            $stored->upstream_id = $upstream->id;
            $stored->name = $name;
            $stored->reference = $key;
            $stored->version = $version;
            $stored->archive_path = $path;
            $stored->shasum = $hash === false ? null : $hash;
            $size = filesize($temp);
            $stored->size = $size === false ? null : $size;
            $stored->last_accessed_at = now();
            $stored->save();

            $this->client->recordSuccess($upstream);

            return $stored;
        } finally {
            @unlink($temp);
        }
    }

    /**
     * Find the dist for the key in the cached (stable and dev) metadata.
     *
     * @return array{array<array-key, mixed>, string}|null
     */
    private function find(Upstream $upstream, string $name, string $key): ?array
    {
        foreach ([false, true] as $dev) {
            $result = $this->metadata->get($upstream, $name, $dev);

            if (! $result->isFound()) {
                continue;
            }

            foreach ($result->versions($name) as $version) {
                $dist = $version['dist'] ?? null;

                if (! is_array($dist) || ! is_string($dist['url'] ?? null) || self::key($dist) !== $key) {
                    continue;
                }

                $versionName = $version['version'] ?? null;

                return [$dist, is_string($versionName) ? $versionName : $key];
            }
        }

        return null;
    }

    private function stored(Upstream $upstream, string $name, string $key): ?MirrorDist
    {
        /** @var MirrorDist|null $stored */
        $stored = MirrorDist::query()
            ->where('upstream_id', $upstream->id)
            ->where('name', $name)
            ->where('reference', $key)
            ->first();

        if ($stored === null) {
            return null;
        }

        if (! Storage::exists($stored->archive_path)) {
            $stored->delete();

            return null;
        }

        return $stored;
    }

    private function touch(MirrorDist $stored): MirrorDist
    {
        if ($stored->last_accessed_at === null || $stored->last_accessed_at->lt(now()->subHour())) {
            $stored->last_accessed_at = now();
            $stored->save();
        }

        return $stored;
    }

    private function isZip(string $path): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        $zip->close();

        return true;
    }
}
