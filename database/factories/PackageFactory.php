<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PackageType;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
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

        return [
            'name' => "$vendor/$name",
            'type' => fake()->randomElement(PackageType::cases()),
        ];
    }
}
