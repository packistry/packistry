<?php

declare(strict_types=1);

namespace App\Enums;

enum RepositorySyncMode: string
{
    case SOURCE = 'source';
    case MANUAL = 'manual';
}
