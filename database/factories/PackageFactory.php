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
        $vendor = $this->faker->word();
        $name = $this->faker->word();

        return [
            'name' => "$vendor/$name",
            'type' => $this->faker->randomElement(PackageType::cases()),
        ];
    }
}
