<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Version;
use App\Traits\ComposerFromZip;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * @extends Factory<Version>
 */
class VersionFactory extends Factory
{
    use ComposerFromZip;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vendor = fake()->word();
        $name = fake()->word();

        $version = fake()->unique()->regexify('\d{1,2}\.\d{1,2}\.\d{1,2}(-alpha|-beta|-RC|-stable)?(\d|\.\d)?');

        return [
            'name' => $version,
            'metadata' => [
                'authors' => [
                    [
                        'name' => fake()->name,
                        'email' => fake()->email,
                    ],
                ],
                'autoload' => [
                    'psr-4' => [
                        "$vendor\\$name\\" => 'src/',
                    ],
                ],
                'bin' => [
                    './bin/script',
                ],
            ],
            'shasum' => fake()->sha256(),
        ];
    }

    /**
     * @throws Throwable
     */
    public function fromDefaultZip(string $version): static
    {
        return $this->fromZip(__DIR__.'/../../tests/Fixtures/gitea-jamie-test.zip', $version);
    }

    /**
     * @throws Throwable
     */
    public function fromZip(string $path, ?string $version = null, string $dir = ''): static
    {
        Storage::fake();

        $decoded = $this->decodedComposerJsonFromZip($path);
        $version ??= $decoded['version'];

        [$vendor, $name] = explode('/', (string) $decoded['name']);
        $archiveName = "$dir$vendor-$name-$version.zip";

        /** @var string $archiveContent */
        $archiveContent = file_get_contents($path);
        Storage::put($archiveName, $archiveContent);

        return $this->state(fn (array $attributes): array => [
            'name' => $version,
            'shasum' => hash_file('sha1', $path),
            'metadata' => collect($decoded)->only([
                'description',
                'readme',
                'keywords',
                'homepage',
                'license',
                'authors',
                'bin',
                'autoload',
                'autoload-dev',
                'extra',
                'require',
                'require-dev',
                'suggest',
                'provide',
            ])->toArray(),
        ]);
    }

    public function name(string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
        ]);
    }
}
