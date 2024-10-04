<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case USER = 'user';

    /**
     * @return Permission[]
     */
    public function permissions(): array
    {
        return config("authorization.$this->value");
    }
}
