<?php

declare(strict_types=1);

namespace App\Console\Commands\Add;

use App\Actions\Repositories\Inputs\StoreRepositoryInput;
use App\Actions\Repositories\StoreRepository;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class AddRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:repository {name?}';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Create a new repository to serve packages from';

    /**
     * Execute the console command.
     */
    public function handle(StoreRepository $storeRepository): int
    {
        $name = text(
            label: 'Name of the repository',
            placeholder: 'Name will also be the path the repository is served from',
            required: true,
        );

        $description = text(
            label: 'Description',
            placeholder: 'Optional description',
        );

        $public = confirm(
            label: 'Make repository public?',
            default: false,
        );

        $storeRepository->handle(new StoreRepositoryInput(
            name: $name,
            description: $description,
            public: $public,
        ));

        $this->info('Repository created');

        return self::SUCCESS;
    }
}
