<?php

declare(strict_types=1);

namespace App\Mirror;

enum MetadataStatus
{
    case FOUND;
    case NOT_FOUND;
    case UNAVAILABLE;
}
