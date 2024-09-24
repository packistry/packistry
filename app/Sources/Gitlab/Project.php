<?php

declare(strict_types=1);

namespace App\Sources\Gitlab;

class Project extends Input
{
    public function __construct(
        public int $id,
        public string $name,
        public string $pathWithNamespace,
        public string $webUrl,
    ) {}
}
