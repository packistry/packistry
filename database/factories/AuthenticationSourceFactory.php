<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuthenticationProvider;
use App\Enums\Role;
use App\Models\AuthenticationSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuthenticationSource>
 */
class AuthenticationSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseUrl = 'https://'.fake()->domainName();

        return [
            'name' => fake()->word,
            'provider' => fake()->randomElement(AuthenticationProvider::cases()),
            'default_user_role' => Role::USER,
            'client_id' => Str::random(),
            'client_secret' => Str::random(),
            'discovery_url' => "$baseUrl/.well-known/openid-configuration",
            'allow_registration' => false,
            'allowed_domains' => [],
        ];
    }
}
