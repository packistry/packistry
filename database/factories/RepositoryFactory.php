<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repository>
 */
class RepositoryFactory extends Factory
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
            'public' => false,
        ];
    }

    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => null,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'public' => true,
        ]);
    }
}
