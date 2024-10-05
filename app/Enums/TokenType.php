<?php

declare(strict_types=1);

namespace App\Enums;

enum TokenType: string
{
    case PERSONAL_ACCESS = 'personal_access';
    case DEPLOY = 'deploy';

    public function prefix(): string
    {
        return match ($this) {
            self::PERSONAL_ACCESS => 'pkpat',
            self::DEPLOY => 'pkdt',
        };
    }
}
