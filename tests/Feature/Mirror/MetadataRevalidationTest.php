<?php

declare(strict_types=1);

use App\Jobs\RevalidateMetadata;
use App\Models\MirrorMetadata;
use App\Models\Upstream;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\getJson;
use function Pest\Laravel\travel;

beforeEach(function (): void {
    Storage::fake();
    Http::preventStrayRequests();
});

it('serves expired metadata without waiting for the upstream', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(
            upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')])
        ),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    Queue::fake();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    Http::assertSentCount(2);
    Queue::assertPushed(RevalidateMetadata::class, 1);
});

it('serves expired metadata right away and revalidates it in the background', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))
            ->push(upstreamMetadata('acme/package', [upstreamVersion('2.0.0', 'bbb')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    Http::assertSentCount(3);

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '2.0.0');

    Http::assertSentCount(3);
});

it('keeps serving revalidated metadata without asking the upstream again', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push(
                upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]),
                headers: ['Last-Modified' => 'Wed, 21 Oct 2026 07:28:00 GMT']
            )
            ->push(null, 304),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))->assertOk();
    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    Http::assertSentCount(3);

    expect(MirrorMetadata::query()->sole()->checked_at?->isCurrentSecond())->toBeTrue();
});

it('waits for the upstream once the stale ttl has passed', function (): void {
    config(['mirror.metadata_stale_ttl' => 3600]);

    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))
            ->push(upstreamMetadata('acme/package', [upstreamVersion('2.0.0', 'bbb')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(300 + 3601)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '2.0.0');
});

it('always waits for the upstream when the stale ttl is disabled', function (): void {
    config(['mirror.metadata_stale_ttl' => 0]);

    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))
            ->push(upstreamMetadata('acme/package', [upstreamVersion('2.0.0', 'bbb')])),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '2.0.0');
});

it('skips the revalidation while another request is revalidating', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::response(
            upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')])
        ),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    Cache::lock("mirror:lock:metadata:$upstream->id:acme/package:stable", 30)->get();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    Http::assertSentCount(2);
});

it('keeps serving the cached metadata when the revalidation fails', function (): void {
    $upstream = Upstream::factory()->packagist()->create();
    $repository = mirroredRepository($upstream, public: true);

    Http::fake([
        'repo.packagist.org/packages.json' => Http::response(['metadata-url' => '/p2/%package%.json']),
        'repo.packagist.org/p2/acme/package.json' => Http::sequence()
            ->push(upstreamMetadata('acme/package', [upstreamVersion('1.0.0', 'aaa')]))
            ->push('down', 503),
    ]);

    getJson($repository->url('/p2/acme/package.json'))->assertOk();

    travel(301)->seconds();

    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    expect($upstream->refresh()->last_error)->toContain('HTTP 503');

    // the upstream is skipped while it is down
    getJson($repository->url('/p2/acme/package.json'))
        ->assertOk()
        ->assertJsonPath('packages.acme/package.0.version', '1.0.0');

    Http::assertSentCount(3);
});
