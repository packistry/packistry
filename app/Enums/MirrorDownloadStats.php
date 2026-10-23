<?php

declare(strict_types=1);

namespace App\Enums;

enum MirrorDownloadStats: string
{
    case FULL = 'full';
    case COUNTS = 'counts';
    case OFF = 'off';
}
