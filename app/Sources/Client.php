<?php

declare(strict_types=1);

namespace App\Sources;

use Illuminate\Http\Client\PendingRequest;

abstract class Client
{
    public function __construct(protected PendingRequest $http)
    {
        //
    }

    /**
     * @return Project[]
     */
    abstract public function projects(): array;

    /**
     * @return Branch[]
     */
    abstract public function branches(Project $project): array;

    /**
     * @return Tag[]
     */
    abstract public function tags(Project $project): array;

    abstract public function createWebhook(Project $project): void;
}
