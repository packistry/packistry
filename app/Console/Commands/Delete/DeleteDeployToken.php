<?php

declare(strict_types=1);

namespace App\Console\Commands\Delete;

use App\Actions\DeployTokens\DestroyDeployToken;
use App\Models\DeployToken;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;

class DeleteDeployToken extends Command
{
    /** @var string */
    protected $signature = 'delete:deploy-token';

    /** @var string|null */
    protected $description = 'Delete a deploy token';

    public function handle(DestroyDeployToken $destroyDeployToken): int
    {
        $tokens = multisearch(
            label: 'Select the deploy tokens to delete',
            options: fn (string $value) => DeployToken::query()
                ->when($value, fn (Builder $query) => $query->where('name', 'like', "$value%"))
                ->get()
                ->keyBy(fn (DeployToken $deployToken): string => (string) $deployToken->id)
                ->map(fn (DeployToken $deployToken): string => $deployToken->name)
                ->toArray(),
            required: true,
        );

        if (! confirm('Do you really want to delete the selected deploy tokens?', default: false)) {
            return self::FAILURE;
        }

        foreach ($tokens as $token) {
            /** @var DeployToken $token */
            $token = DeployToken::query()->findOrFail($token);
            $destroyDeployToken->handle($token);

            $this->info("Deploy token $token->name deleted");
        }

        return self::SUCCESS;
    }
}
