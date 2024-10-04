<?php

declare(strict_types=1);

namespace App\Console\Commands\Add;

use App\Actions\DeployTokens\Inputs\StoreDeployTokenInput;
use App\Actions\DeployTokens\StoreDeployToken;
use App\Enums\TokenAbility;
use App\Models\Repository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\text;

class AddDeployToken extends Command
{
    /** @var string */
    protected $signature = 'packistry:add:deploy-token';

    /** @var string|null */
    protected $description = 'Add a deploy token';

    public function handle(StoreDeployToken $storeDeployToken): int
    {
        $name = text(
            label: 'Name',
            placeholder: 'Name',
            required: true
        );

        /** @var string[] $repositories */
        $repositories = multisearch(
            label: 'Select repositories this user will have access to',
            options: fn (string $value) => Repository::query()
                ->where('public', false)
                ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                ->get()
                ->keyBy(fn (Repository $repository): string => (string) $repository->id)
                ->map(fn (Repository $repository): string => $repository->name ?? 'Root')
                ->toArray(),
            required: true,
        );

        [,$accessToken] = $storeDeployToken->handle(new StoreDeployTokenInput(
            name: $name,
            abilities: [
                TokenAbility::REPOSITORY_READ->value,
            ],
            repositories: $repositories,
        ));

        $this->info('Deploy token created');
        $this->info($accessToken->plainTextToken);

        return self::SUCCESS;
    }
}
