<?php

declare(strict_types=1);

namespace App\Enums;

enum SourceProvider: string
{
    case GITEA = 'gitea';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
}
