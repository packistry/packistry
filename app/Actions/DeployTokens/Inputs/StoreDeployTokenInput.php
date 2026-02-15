<?php

declare(strict_types=1);

namespace App\Actions\DeployTokens\Inputs;

use App\Actions\Input;
use App\Enums\TokenAbility;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;

class StoreDeployTokenInput extends Input
{
    /**
     * @param  string[]  $abilities
     * @param  string[]  $repositories
     * @param  int[]|null  $packages
     */
    public function __construct(
        public string $name,
        public array $abilities,
        public ?Carbon $expiresAt = null,
        public ?array $repositories = [],
        public ?array $packages = null
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'abilities.*' => [new Enum(TokenAbility::class)],
            'packages' => ['nullable', 'array'],
            'packages.*' => ['integer', 'exists:packages,id'],
        ];
    }
}
