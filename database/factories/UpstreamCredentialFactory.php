<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UpstreamCredentialType;
use App\Models\UpstreamCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UpstreamCredential>
 */
class UpstreamCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'host' => 'repo.packagist.org',
            'type' => UpstreamCredentialType::HTTP_BASIC,
            'username' => 'user',
            'secret' => 'secret',
        ];
    }
}
