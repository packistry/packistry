<?php

declare(strict_types=1);

namespace App\Console\Commands\Add;

use App\Actions\Sources\Inputs\StoreSourceInput;
use App\Actions\Sources\StoreSource;
use App\Enums\SourceProvider;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddSource extends Command
{
    /** @var string */
    protected $signature = 'add:source';

    /** @var string|null */
    protected $description = 'Add a source from where you will be providing packages';

    public function handle(StoreSource $storeSource): int
    {
        $name = text(
            label: 'Name of the package source',
            placeholder: 'My package source',
            required: true
        );

        $provider = select(
            label: 'Select your provider',
            options: array_map(fn (SourceProvider $provider) => $provider->value, SourceProvider::cases()),
            default: app()->isProduction() ? '' : SourceProvider::GITEA->value
        );

        $url = text(
            label: 'Base url',
            placeholder: 'https://github.com',
            default: app()->isProduction() ? '' : 'http://localhost:3000',
            required: true
        );

        $token = text(
            label: 'Access token for provider',
            required: true,
        );

        $storeSource->handle(new StoreSourceInput(
            name: $name,
            provider: SourceProvider::from($provider),
            url: $url,
            token: $token,
        ));

        $this->info('Source created');

        return self::SUCCESS;
    }
}
