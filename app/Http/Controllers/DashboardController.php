<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\DeployToken;
use App\Models\Download;
use App\Models\Package;
use App\Models\Repository;
use App\Models\Source;
use App\Models\User;

readonly class DashboardController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $this->authorize(Permission::DASHBOARD);

        /** @var User $user */
        $user = auth()->user();

        $dashboard = [];

        if ($user->can(Permission::PACKAGE_READ)) {
            $dashboard['packages'] = Package::userScoped()->count();
        }

        if ($user->can(Permission::REPOSITORY_READ)) {
            $dashboard['repositories'] = Repository::userScoped()->count();
        }

        if ($user->can(Permission::USER_READ)) {
            $dashboard['users'] = User::query()->count();
        }

        if ($user->can(Permission::DEPLOY_TOKEN_READ)) {
            $dashboard['tokens'] = DeployToken::query()->count();
        }

        if ($user->can(Permission::SOURCE_READ)) {
            $dashboard['sources'] = Source::query()->count();
        }

        $dashboard['downloads'] = Download::query()->count();

        return $dashboard;
    }
}
