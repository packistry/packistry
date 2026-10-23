<?php

declare(strict_types=1);

namespace App\Actions\Repositories;

use App\Actions\Repositories\Exceptions\RepositoryAlreadyExistsException;
use App\Actions\Repositories\Inputs\UpdateRepositoryInput;
use App\Models\Repository;
use App\Models\Upstream;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateRepository
{
    public function handle(Repository $repository, UpdateRepositoryInput $input): Repository
    {
        $path = is_null($input->path) ? null : Str::slug($input->path);

        if (Repository::isPathInUse($path, exclude: $repository->id)) {
            throw new RepositoryAlreadyExistsException;
        }

        $repository->name = $input->name;
        $repository->path = $path;

        $repository->description = $input->description;
        $repository->public = $input->public;

        if ($repository->public) {
            $private = $repository->upstreams()
                ->with('credentials')
                ->get()
                ->filter(fn (Upstream $upstream): bool => $upstream->isPrivate());

            if ($private->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'public' => 'Repositories with authenticated upstreams cannot be public: '.$private->pluck('name')->implode(', '),
                ]);
            }
        }

        $repository->save();

        return $repository;
    }
}
