<?php

declare(strict_types=1);

namespace App\Actions\Repositories;

use App\Actions\Repositories\Exceptions\RepositoryAlreadyExistsException;
use App\Actions\Repositories\Inputs\UpdateRepositoryInput;
use App\Models\Repository;

class UpdateRepository
{
    public function handle(Repository $repository, UpdateRepositoryInput $input): Repository
    {
        if (Repository::isNameInUse($input->name, exclude: $repository->id)) {
            throw new RepositoryAlreadyExistsException;
        }

        $repository->name = $input->name;
        $repository->description = $input->description;
        $repository->public = $input->public;

        $repository->save();

        return $repository;
    }
}
