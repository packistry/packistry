<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

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
            'path' => fake()->name(),
            'public' => false,
        ];
    }

    public function root(): static
    {
        return $this->state(fn (array $attributes): array => [
            'path' => null,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes): array => [
            'public' => true,
        ]);
    }

    public function withPackages(int $count = 10, ?string $prefix = null, ?string $type = null): static
    {
        $prefix ??= fake()->slug(nbWords: 2);

        return $this->has(
            Package::factory()
                ->state(new Sequence(function (Sequence $sequence) use ($prefix) {
                    $number = $sequence->index + 1;

                    return [
                        'name' => "{$prefix}/package-{$number}",
                    ];
                }))
                ->when($type, fn (PackageFactory $factory) => $factory->state(['type' => $type]))
                ->count($count),
        );
    }
}
