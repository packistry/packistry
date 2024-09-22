<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

class Commit extends Input
{
    public function __construct(
        public string $id,
        public string $message,
        public string $url,
        public Author $author,
        public Author $committer,
        public ?string $verification,
        public string $timestamp,
    ) {}
}
