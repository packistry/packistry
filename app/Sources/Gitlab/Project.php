<?php

declare(strict_types=1);

namespace App\Sources\Gitlab;

use App\Sources\Gitea\Input;

class Project extends Input
{
    public function __construct(
        public int $id,
        public string $name,
        public string $pathWithNamespace,
        public string $webUrl,
    ) {}
}
