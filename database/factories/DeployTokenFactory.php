<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeployToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeployToken>
 */
class DeployTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}
