<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UpstreamCredentialType;
use App\Models\Upstream;
use App\Models\UpstreamCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Upstream>
 */
class UpstreamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => 'https://repo.packagist.org',
        ];
    }

    public function packagist(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Packagist',
            'url' => 'https://repo.packagist.org',
        ]);
    }

    public function url(string $url): static
    {
        return $this->state(fn (array $attributes): array => [
            'url' => $url,
        ]);
    }

    /**
     * @param  string[]|null  $only
     * @param  string[]|null  $exclude
     */
    public function filters(?array $only = null, ?array $exclude = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'only' => $only,
            'exclude' => $exclude,
        ]);
    }

    public function withCredential(
        string $host,
        UpstreamCredentialType $type = UpstreamCredentialType::HTTP_BASIC,
        ?string $username = 'user',
        string $secret = 'secret',
    ): static {
        return $this->has(
            UpstreamCredential::factory()->state([
                'host' => $host,
                'type' => $type,
                'username' => $username,
                'secret' => $secret,
            ]),
            'credentials'
        );
    }
}
