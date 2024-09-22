<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

class RepositoryOwner extends Input
{
    public function __construct(
        public int $id,
        public string $login,
        public ?string $loginName,
        public int $sourceId,
        public ?string $fullName,
        public string $email,
        public string $avatarUrl,
        public string $htmlUrl,
        public ?string $language,
        public bool $isAdmin,
        public string $lastLogin,
        public string $created,
        public bool $restricted,
        public bool $active,
        public bool $prohibitLogin,
        public ?string $location,
        public ?string $website,
        public ?string $description,
        public string $visibility,
        public int $followersCount,
        public int $followingCount,
        public int $starredReposCount,
        public string $username
    ) {}
}
