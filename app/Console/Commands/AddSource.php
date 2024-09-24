<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SourceProvider;
use App\Models\Source;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddSource extends Command
{
    /** @var string */
    protected $signature = 'conductor:add:source';

    /** @var string|null */
    protected $description = 'Add a source from where you will be providing packages';

    public function handle(): int
    {
        $name = text(
            label: 'Name of the package source',
            default: app()->isProduction() ? '' : 'Source',
            required: true
        );

        $provider = select(
            label: 'Select your provider',
            options: array_map(fn (SourceProvider $provider) => $provider->value, SourceProvider::cases()),
            default: app()->isProduction() ? '' : SourceProvider::GITEA->value
        );

        $url = text(
            label: 'Base url e.g https://github.com',
            default: app()->isProduction() ? '' : 'http://localhost:3000',
            required: true
        );

        $token = text(
            label: 'Access token for provider',
        );

        $source = new Source;

        $source->name = $name;
        $source->provider = SourceProvider::from($provider);
        $source->url = $url;
        $source->token = encrypt($token);

        $source->save();

        return self::SUCCESS;
    }
}
