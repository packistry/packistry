import { z } from 'zod'

export const DASHBOARD = 'dashboard'
export const UNSCOPED = 'unscoped'
export const REPOSITORY_CREATE = 'repository_create'
export const REPOSITORY_READ = 'repository_read'
export const REPOSITORY_UPDATE = 'repository_update'
export const REPOSITORY_DELETE = 'repository_delete'
export const PACKAGE_CREATE = 'package_create'
export const PACKAGE_READ = 'package_read'
export const PACKAGE_UPDATE = 'package_update'
export const PACKAGE_DELETE = 'package_delete'
export const USER_CREATE = 'user_create'
export const USER_READ = 'user_read'
export const USER_UPDATE = 'user_update'
export const USER_DELETE = 'user_delete'
export const SOURCE_CREATE = 'source_create'
export const SOURCE_READ = 'source_read'
export const SOURCE_UPDATE = 'source_update'
export const SOURCE_DELETE = 'source_delete'
export const DEPLOY_TOKEN_CREATE = 'deploy_token_create'
export const DEPLOY_TOKEN_READ = 'deploy_token_read'
export const DEPLOY_TOKEN_UPDATE = 'deploy_token_update'
export const DEPLOY_TOKEN_DELETE = 'deploy_token_delete'
export const PERSONAL_TOKEN_CREATE = 'personal_token_create'
export const PERSONAL_TOKEN_READ = 'personal_token_read'
export const PERSONAL_TOKEN_UPDATE = 'personal_token_update'
export const PERSONAL_TOKEN_DELETE = 'personal_token_delete'
export const AUTHENTICATION_SOURCE_CREATE = 'authentication_source_create'
export const AUTHENTICATION_SOURCE_READ = 'authentication_source_read'
export const AUTHENTICATION_SOURCE_UPDATE = 'authentication_source_update'
export const AUTHENTICATION_SOURCE_DELETE = 'authentication_source_delete'
export const BATCH_READ = 'batch_read'
export const BATCH_DELETE = 'batch_delete'

export const permissions = [
    DASHBOARD,
    UNSCOPED,
    REPOSITORY_CREATE,
    REPOSITORY_READ,
    REPOSITORY_UPDATE,
    REPOSITORY_DELETE,
    PACKAGE_CREATE,
    PACKAGE_READ,
    PACKAGE_UPDATE,
    PACKAGE_DELETE,
    USER_CREATE,
    USER_READ,
    USER_UPDATE,
    USER_DELETE,
    SOURCE_CREATE,
    SOURCE_READ,
    SOURCE_UPDATE,
    SOURCE_DELETE,
    DEPLOY_TOKEN_CREATE,
    DEPLOY_TOKEN_READ,
    DEPLOY_TOKEN_UPDATE,
    DEPLOY_TOKEN_DELETE,
    PERSONAL_TOKEN_CREATE,
    PERSONAL_TOKEN_READ,
    PERSONAL_TOKEN_UPDATE,
    PERSONAL_TOKEN_DELETE,
    AUTHENTICATION_SOURCE_CREATE,
    AUTHENTICATION_SOURCE_READ,
    AUTHENTICATION_SOURCE_UPDATE,
    AUTHENTICATION_SOURCE_DELETE,
    BATCH_READ,
    BATCH_DELETE,
] as const

export const permission = z.enum(permissions)

export type Permission = z.infer<typeof permission>
