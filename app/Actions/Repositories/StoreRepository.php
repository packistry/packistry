<?php

declare(strict_types=1);

namespace App\Actions\Repositories;

use App\Actions\Repositories\Exceptions\RepositoryAlreadyExistsException;
use App\Actions\Repositories\Inputs\StoreRepositoryInput;
use App\Models\Repository;

class StoreRepository
{
    public function handle(StoreRepositoryInput $input): Repository
    {
        if (Repository::isNameInUse($input->name)) {
            throw new RepositoryAlreadyExistsException;
        }

        $repository = new Repository;

        $repository->name = $input->name;
        $repository->description = $input->description;
        $repository->public = $input->public;

        $repository->save();

        return $repository;
    }
}
