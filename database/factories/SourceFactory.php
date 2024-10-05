<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SourceProvider;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Source>
 */
class SourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'provider' => fake()->randomElement(SourceProvider::cases()),
            'url' => fake()->url,
            'token' => encrypt(Str::random()),
            'secret' => encrypt(Str::random()),
        ];
    }

    public function provider(SourceProvider $provider): static
    {
        return $this
            ->state([
                'provider' => $provider,
                'secret' => encrypt('secret'),
                'url' => match ($provider) {
                    SourceProvider::GITEA => 'https://gitea.com',
                    SourceProvider::GITLAB => 'https://gitlab.com',
                    SourceProvider::GITHUB => 'https://api.github.com',
                },
            ]);
    }
}
