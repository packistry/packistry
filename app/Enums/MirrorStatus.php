<?php

declare(strict_types=1);

namespace App\Enums;

enum MirrorStatus: string
{
    case OK = 'ok';
    case GONE = 'gone';
}
