<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case DASHBOARD = 'dashboard';
    case UNSCOPED = 'unscoped';

    case REPOSITORY_CREATE = 'repository_create';
    case REPOSITORY_READ = 'repository_read';
    case REPOSITORY_UPDATE = 'repository_update';
    case REPOSITORY_DELETE = 'repository_delete';

    case PACKAGE_CREATE = 'package_create';
    case PACKAGE_READ = 'package_read';
    case PACKAGE_UPDATE = 'package_update';
    case PACKAGE_DELETE = 'package_delete';

    case USER_CREATE = 'user_create';
    case USER_READ = 'user_read';
    case USER_UPDATE = 'user_update';
    case USER_DELETE = 'user_delete';

    case SOURCE_CREATE = 'source_create';
    case SOURCE_READ = 'source_read';
    case SOURCE_UPDATE = 'source_update';
    case SOURCE_DELETE = 'source_delete';

    case DEPLOY_TOKEN_CREATE = 'deploy_token_create';
    case DEPLOY_TOKEN_READ = 'deploy_token_read';
    case DEPLOY_TOKEN_UPDATE = 'deploy_token_update';
    case DEPLOY_TOKEN_DELETE = 'deploy_token_delete';

    case PERSONAL_TOKEN_CREATE = 'personal_token_create';
    case PERSONAL_TOKEN_READ = 'personal_token_read';
    case PERSONAL_TOKEN_UPDATE = 'personal_token_update';
    case PERSONAL_TOKEN_DELETE = 'personal_token_delete';

    case AUTHENTICATION_SOURCE_CREATE = 'authentication_source_create';
    case AUTHENTICATION_SOURCE_READ = 'authentication_source_read';
    case AUTHENTICATION_SOURCE_UPDATE = 'authentication_source_update';
    case AUTHENTICATION_SOURCE_DELETE = 'authentication_source_delete';

    case BATCH_READ = 'batch_read';
    case BATCH_DELETE = 'batch_delete';
}
