<?php

declare(strict_types=1);

namespace App\Sources;

readonly class Project
{
    public function __construct(
        public int|string $id,
        public string $fullName,
        public string $name,
        public string $url,
        public string $webUrl,
    ) {
        //
    }
}
