<?php

declare(strict_types=1);

namespace App\Actions\Repositories;

use App\Models\Repository;

class DestroyRepository
{
    public function handle(Repository $repository): Repository
    {
        $repository->delete();

        return $repository;
    }
}
