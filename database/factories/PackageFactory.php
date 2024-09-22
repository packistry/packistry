<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PackageType;
use App\Models\Package;
use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

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

    public function name(string $name): static
    {
        return $this
            ->state(['name' => $name]);
    }

    public function versions(int $count = 0): static
    {
        return $this
            ->has(
                Version::factory()
                    ->state(new Sequence(
                        fn (Sequence $sequence): array => ['name' => '0.1.'.$sequence->index],
                    ))
                    ->count($count)
            );
    }

    public function devVersions(int $count = 0): static
    {
        return $this
            ->has(
                Version::factory()
                    ->state(new Sequence(
                        fn (Sequence $sequence): array => ['name' => 'dev-patch-'.$sequence->index],
                    ))
                    ->count($count)
            );
    }
}
