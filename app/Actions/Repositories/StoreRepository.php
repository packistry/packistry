<?php

declare(strict_types=1);

namespace App\Actions\Repositories;

use App\Actions\Repositories\Exceptions\RepositoryAlreadyExistsException;
use App\Actions\Repositories\Inputs\StoreRepositoryInput;
use App\Models\Repository;
use Illuminate\Support\Str;

class StoreRepository
{
    public function handle(StoreRepositoryInput $input): Repository
    {
        if (Repository::isPathInUse($input->path)) {
            throw new RepositoryAlreadyExistsException;
        }

        $repository = new Repository;

        $repository->name = $input->name;
        $repository->path = is_null($input->path)
            ? $input->path
            : Str::slug($input->path);

        $repository->description = $input->description;
        $repository->public = $input->public;

        $repository->save();

        return $repository;
    }
}
