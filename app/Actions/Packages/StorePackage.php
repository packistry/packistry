<?php

declare(strict_types=1);

namespace App\Actions\Packages;

use App\Actions\Packages\Inputs\StorePackageInput;
use App\Enums\PackageType;
use App\Exceptions\ArchiveInvalidContentTypeException;
use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToFetchArchiveException;
use App\Exceptions\FailedToOpenArchiveException;
use App\Exceptions\NameNotFoundException;
use App\Exceptions\VersionNotFoundException;
use App\Jobs\ImportBranches;
use App\Jobs\ImportTags;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Sources\Project;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Throwable;

class StorePackage
{
    /**
     * @return Package[]
     *
     * @throws ArchiveInvalidContentTypeException
     * @throws ComposerJsonNotFoundException
     * @throws FailedToFetchArchiveException
     * @throws FailedToOpenArchiveException
     * @throws NameNotFoundException
     * @throws VersionNotFoundException
     * @throws ConnectionException
     * @throws Throwable
     */
    public function handle(StorePackageInput $input): array
    {
        /** @var Repository $repository */
        $repository = Repository::query()->findOrFail($input->repository);

        /** @var Source $source */
        $source = Source::query()->findOrFail($input->source);

        $client = $source->client();

        $projects = array_map(fn (string $id): Project => $client->project($id), $input->projects);

        $packages = [];

        foreach ($projects as $project) {
            if ($input->webhook) {
                try {
                    $source->client()->createWebhook($repository, $project, $source);
                } catch (RequestException $e) {
                    throw ValidationException::withMessages([
                        'projects' => [
                            "Failed to create webhook for $project->fullName: {$e->response->body()}",
                        ],
                    ]);
                }
            }

            /** @var Package $package */
            $package = $repository
                ->packages()
                ->where('source_id', $source->id)
                ->where('provider_id', $project->id)
                ->first() ?? $repository->packages()->make();

            if (! $package->exists) {
                $package->provider_id = (string) $project->id;
                $package->source_id = $source->id;

                $package->name = 'Importing '.$project->fullName;
                $package->type = PackageType::LIBRARY->value;

                $package->save();
            }

            Bus::batch([
                new ImportBranches($source, $package, $project),
                new ImportTags($source, $package, $project),
            ])
                ->finally(function () use ($package): void {
                    if (! str_starts_with($package->name, 'Importing')) {
                        return;
                    }

                    $package->delete();
                })
                ->dispatch();

            $packages[] = $package;
        }

        return $packages;
    }
}
