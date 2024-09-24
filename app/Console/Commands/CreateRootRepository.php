<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Repository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CreateRootRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conductor:add:repository {name?}';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Create a new repository to serve packages from';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        $exists = Repository::query()
            ->when(
                is_null($name),
                fn (Builder $query) => $query->whereNull('name'),
                fn (Builder $query) => $query->where('name', $name),
            )
            ->exists();

        if ($exists) {
            $this->error('Repository already exists!');

            return self::FAILURE;
        }

        $repository = new Repository;

        $repository->name = $name;

        $repository->save();

        return self::SUCCESS;
    }
}
