<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Repository;
use Illuminate\Console\Command;

class CreateRootRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-root-repository';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Creates root repository';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Repository::query()->whereNull('name')->exists()) {
            $this->error('Repository already exists!');

            return self::FAILURE;
        }

        (new Repository)->save();

        return self::SUCCESS;
    }
}
