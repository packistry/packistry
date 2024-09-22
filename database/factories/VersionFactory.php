<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Version>
 */
class VersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vendor = fake()->word();
        $name = fake()->word();

        $major = fake()->numberBetween(0, 10);
        $minor = fake()->numberBetween(0, 10);
        $patch = fake()->numberBetween(0, 10);

        $version = "$major.$minor.$patch";

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

    public function fromZip(string $path, ?string $version = null): static
    {
        Storage::fake();

        /** @var string $content */
        $content = file_get_contents("zip://$path#composer.json");

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true);
        $version ??= $decoded['version'];

        [$vendor, $name] = explode('/', (string) $decoded['name']);
        $archiveName = "$vendor-$name-$version.zip";

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
}
