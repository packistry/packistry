<?php

declare(strict_types=1);

namespace App\Enums;

enum UpstreamCredentialType: string
{
    case HTTP_BASIC = 'http-basic';
    case BEARER = 'bearer';
    case HEADER = 'header';

    public function requiresUsername(): bool
    {
        return $this !== self::BEARER;
    }
}
