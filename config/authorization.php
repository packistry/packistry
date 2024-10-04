<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;

return [
    Role::ADMIN->value => Permission::cases(),
    Role::USER->value => [
        Permission::DASHBOARD,

        Permission::REPOSITORY_READ,
        Permission::PACKAGE_READ,

        Permission::PERSONAL_TOKEN_CREATE,
        Permission::PERSONAL_TOKEN_READ,
        Permission::PERSONAL_TOKEN_UPDATE,
        Permission::PERSONAL_TOKEN_DELETE,
    ],
];
