<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Version;
use Illuminate\Database\Eloquent\Factories\Factory;

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
                        'name' => fake()->name,
                        'email' => fake()->email,
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
        ];
    }
}
