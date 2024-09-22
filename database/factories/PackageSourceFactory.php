<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PackageSourceProvider;
use App\Models\PackageSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PackageSource>
 */
class PackageSourceFactory extends Factory
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
            'provider' => fake()->randomElement(PackageSourceProvider::cases()),
            'token' => Str::random(),
        ];
    }
}
