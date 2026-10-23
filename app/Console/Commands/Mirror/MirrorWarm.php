<?php

declare(strict_types=1);

namespace App\Console\Commands\Mirror;

use App\Enums\MirrorStatus;
use App\Exceptions\UpstreamUnavailableException;
use App\Mirror\DistCache;
use App\Mirror\Resolver;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Upstream;
use Illuminate\Console\Command;
use JsonException;

class MirrorWarm extends Command
{
    protected $signature = 'mirror:warm
        {lock : Path to a composer.lock file}
        {--repository= : Path of the repository to warm, leave empty for the root repository}';

    protected $description = 'Cache the metadata and dists of every package in a composer.lock, so they are available when an upstream is down';

    public function handle(Resolver $resolver, DistCache $dists): int
    {
        $path = $this->argument('lock');
        $contents = is_file($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            $this->error("Could not read $path.");

            return self::FAILURE;
        }

        try {
            $lock = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error("The lock file is not valid JSON: {$e->getMessage()}");

            return self::FAILURE;
        }

        $repositoryPath = $this->option('repository');

        /** @var Repository|null $repository */
        $repository = Repository::query()
            ->queryByPath(is_string($repositoryPath) && $repositoryPath !== '' ? $repositoryPath : null)
            ->first();

        if ($repository === null) {
            $this->error('Repository not found.');

            return self::FAILURE;
        }

        $packages = array_merge(
            is_array($lock['packages'] ?? null) ? $lock['packages'] : [],
            is_array($lock['packages-dev'] ?? null) ? $lock['packages-dev'] : [],
        );

        $rows = [];
        $failed = false;

        foreach ($packages as $locked) {
            $name = is_string($locked['name'] ?? null) ? $locked['name'] : null;
            $version = is_string($locked['version'] ?? null) ? $locked['version'] : '';

            if ($name === null) {
                continue;
            }

            [$status, $ok] = $this->warm($repository, $resolver, $dists, $name, $version, $locked['dist'] ?? null);

            $failed = $failed || ! $ok;
            $rows[] = [$name, $version, $status];
        }

        $this->table(['Package', 'Version', 'Result'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{string, bool}
     */
    private function warm(Repository $repository, Resolver $resolver, DistCache $dists, string $name, string $version, mixed $dist): array
    {
        /** @var Package|null $package */
        $package = $repository->packages()->where('name', $name)->first();

        if ($package !== null && ! $package->isMirrored()) {
            return ['local package', true];
        }

        if ($package === null || $package->mirror_status === MirrorStatus::GONE) {
            $dev = str_starts_with($version, 'dev-') || str_ends_with($version, '-dev');
            $package = $resolver->resolve($repository, $name, $dev, $package);
        }

        if ($package === null) {
            return ['<error>not found in any upstream</error>', false];
        }

        if (! is_array($dist) || ! is_string($dist['url'] ?? null)) {
            return ['metadata cached (no dist)', true];
        }

        /** @var Upstream $upstream */
        $upstream = Upstream::query()->with('credentials')->findOrFail($package->upstream_id);

        try {
            $stored = $dists->get($upstream, $name, DistCache::key($dist));
        } catch (UpstreamUnavailableException $e) {
            return ["<error>dist download failed: {$e->getMessage()}</error>", false];
        }

        if ($stored === null) {
            return ['<error>version not found upstream</error>', false];
        }

        return ["<info>cached</info> from $upstream->name", true];
    }
}
