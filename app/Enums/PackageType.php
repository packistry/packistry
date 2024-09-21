<?php

declare(strict_types=1);

namespace App\Enums;

enum PackageType: string
{
    case LIBRARY = 'library';
    case PROJECT = 'project';
    case COMPOSER_PLUGIN = 'composer-plugin';
    case METAPACKAGE = 'metapackage';
}
