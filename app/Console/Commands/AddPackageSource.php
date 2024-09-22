<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PackageSourceProvider;
use App\Models\PackageSource;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddPackageSource extends Command
{
    /** @var string */
    protected $signature = 'app:add-package-source';

    /** @var string|null */
    protected $description = 'Add package source';

    public function handle(): int
    {
        $name = text(
            label: 'Name of the package source',
            default: app()->isProduction() ? '' : 'Source',
            required: true
        );

        $provider = select(
            label: 'Select your provider',
            options: array_map(fn (PackageSourceProvider $provider) => $provider->value, PackageSourceProvider::cases()),
            default: app()->isProduction() ? '' : PackageSourceProvider::GITEA->value
        );

        $url = text(
            label: 'Base url e.g https://github.com',
            default: app()->isProduction() ? '' : 'http://localhost:3000',
            required: true
        );

        $token = text(
            label: 'Access token for provider',
        );

        $source = new PackageSource;

        $source->name = $name;
        $source->provider = PackageSourceProvider::from($provider);
        $source->url = $url;
        $source->token = encrypt($token);

        $source->save();

        return self::SUCCESS;
    }
}
