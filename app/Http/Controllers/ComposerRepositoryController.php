<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Repository;
use App\Models\Version;
use Illuminate\Http\Request;

class ComposerRepositoryController extends Controller
{
    public function repository(): Repository
    {
        $name = request()->route('repository');

        return Repository::query()
            ->where('name', $name)
            ->firstOrFail();
    }

    public function packages(): array
    {
        return [
            'search' => url('/search.json?q=%query%&type=%type%'),
            'metadata-url' => url('/p2/%package%.json'),
            'list' => url('/list.json'),
        ];
    }

    public function search(Request $request): array
    {
        $query = $request->input('q');

        $packagesQuery = $this->repository()
            ->packages()
            ->where('name', 'like', "$query%");

        return [
            'total' => $packagesQuery->count(),
            'results' => $packagesQuery->chunkMap(function (Package $package) {
                return [
                    'name' => $package->name,
                    'description' => '',
                    'downloads' => 0,
                ];
            }),
        ];
    }

    public function list(): array
    {
        $names = $this->repository()
            ->packages()
            ->pluck('name');

        return [
            'packageNames' => $names,
        ];
    }

    public function package(string $vendor, string $name): array
    {
        $package = $this
            ->repository()
            ->packages()
            ->where('name', "$vendor/$name")
            ->firstOrFail();

        return [
            'minified' => 'composer/2.0',
            'packages' => [
                $package->name => $package->versions->map(fn (Version $version) => [
                    ...$version->metadata,
                    'name' => $package->name,
                    'version' => $version->name,
                    'type' => 'library',
                    'time' => $version->created_at,
                    'dist' => [
                        'type' => 'zip',
                        'url' => 'https://git.qlic.nl/api/packages/qlic/composer/files/qlic%2Fquality/0.13.5/qlic-quality.0.13.5.zip',
                        'shasum' => '91647ebf517448c75898413b3da71de6e372f6df',
                    ],
                ]),
            ],
        ];
    }

    public function packageDev(): string
    {
        return 'welcome';
    }

    public function download(): string
    {
        return 'welcome';
    }

    public function upload(): string
    {
        return 'welcome';
    }
}
