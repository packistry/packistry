<?php

declare(strict_types=1);

namespace App\Actions\PersonalTokens\Inputs;

use App\Actions\Input;
use App\Enums\TokenAbility;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Enum;

class StorePersonalTokenInput extends Input
{
    /**
     * @param  string[]  $abilities
     */
    public function __construct(
        public string $name,
        public array $abilities,
        public ?Carbon $expiresAt = null,
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
        ];
    }
}
