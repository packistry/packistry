<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

class Author extends Input
{
    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?string $username
    ) {}
}
