<?php

declare(strict_types=1);

use App\Enums\MirrorStatus;
use App\Enums\TokenAbility;
use App\Models\Package;
use App\Models\Upstream;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;

beforeEach(function (): void {
    Storage::fake();
    Http::preventStrayRequests();

    $this->gitlab = Upstream::factory()->url('https://gitlab.test/packages.json')->create();
    $this->packagist = Upstream::factory()->packagist()->create();

    $this->repository = mirroredRepository([$this->gitlab, $this->packagist]);

    Http::fake([
        'gitlab.test/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'gitlab.test/p2/acme/private.json' => Http::response(upstreamMetadata('acme/private', [upstreamVersion('1.0.0', 'aaa')])),
        'gitlab.test/p2/*' => Http::response(null, 404),
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/vendor/public.json' => Http::response(upstreamMetadata('vendor/public', [upstreamVersion('1.0.0', 'bbb')])),
        'repo.packagist.org/p2/acme/private.json' => Http::response(upstreamMetadata('acme/private', [upstreamVersion('6.6.6', 'evil')])),
        'repo.packagist.org/p2/*' => Http::response(null, 404),
    ]);
});

it('gives repository access to all mirrored packages', function (): void {
    deployToken(TokenAbility::REPOSITORY_READ, withAccess: true);

    getJson($this->repository->url('/p2/acme/private.json'))->assertOk();
    getJson($this->repository->url('/p2/vendor/public.json'))->assertOk();
});

it('gives package access to individual mirrored packages', function (): void {
    $private = Package::factory()
        ->for($this->repository)
        ->name('acme/private')
        ->state(['upstream_id' => $this->gitlab->id, 'mirror_status' => MirrorStatus::OK])
        ->create();

    Package::factory()
        ->for($this->repository)
        ->name('vendor/public')
        ->state(['upstream_id' => $this->packagist->id, 'mirror_status' => MirrorStatus::OK])
        ->create();

    deployToken(TokenAbility::REPOSITORY_READ, withPackages: [$private->id]);

    getJson($this->repository->url('/p2/acme/private.json'))->assertOk();
    getJson($this->repository->url('/p2/vendor/public.json'))->assertNotFound();
});

it('denies tokens without access', function (): void {
    deployToken(TokenAbility::REPOSITORY_READ);

    getJson($this->repository->url('/p2/acme/private.json'))->assertNotFound();
});

it('does not give mirrored packages to tokens that can only access other packages', function (): void {
    $local = Package::factory()->for($this->repository)->name('acme/local')->create();

    deployToken(TokenAbility::REPOSITORY_READ, withPackages: [$local->id]);

    getJson($this->repository->url('/p2/vendor/public.json'))->assertNotFound();
});
