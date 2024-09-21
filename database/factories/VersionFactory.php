<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use function PHPUnit\Framework\assertNotFalse;

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
        $vendor = $this->faker->word();
        $name = $this->faker->word();

        $major = $this->faker->numberBetween(0, 10);
        $minor = $this->faker->numberBetween(0, 10);
        $patch = $this->faker->numberBetween(0, 10);

        $version = "$major.$minor.$patch";

        return [
            'name' => $version,
            'metadata' => [
                'authors' => [
                    [
                        'name' => $this->faker->name,
                        'email' => $this->faker->email,
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
            'shasum' => $this->faker->sha256(),
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

        return $this->state(fn (array $attributes) => [
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
