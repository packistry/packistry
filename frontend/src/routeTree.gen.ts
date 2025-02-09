/* eslint-disable */

// @ts-nocheck

// noinspection JSUnusedGlobalSymbols

// This file was automatically generated by TanStack Router.
// You should NOT make any changes in this file as it will be overwritten.
// Additionally, you should also exclude this file from your linter and/or formatter to prevent it from being checked or modified.

// Import Routes

import { Route as rootRoute } from './routes/__root'
import { Route as LoginImport } from './routes/login'
import { Route as AuthImport } from './routes/_auth'
import { Route as AuthIndexImport } from './routes/_auth.index'
import { Route as AuthUsersImport } from './routes/_auth.users'
import { Route as AuthSourcesImport } from './routes/_auth.sources'
import { Route as AuthRepositoriesImport } from './routes/_auth.repositories'
import { Route as AuthPersonalTokensImport } from './routes/_auth.personal-tokens'
import { Route as AuthDeployTokensImport } from './routes/_auth.deploy-tokens'
import { Route as AuthPackagesIndexImport } from './routes/_auth.packages.index'
import { Route as AuthPackagesPackageIdImport } from './routes/_auth.packages.$packageId'

// Create/Update Routes

const LoginRoute = LoginImport.update({
    id: '/login',
    path: '/login',
    getParentRoute: () => rootRoute,
} as any)

const AuthRoute = AuthImport.update({
    id: '/_auth',
    getParentRoute: () => rootRoute,
} as any)

const AuthIndexRoute = AuthIndexImport.update({
    id: '/',
    path: '/',
    getParentRoute: () => AuthRoute,
} as any)

const AuthUsersRoute = AuthUsersImport.update({
    id: '/users',
    path: '/users',
    getParentRoute: () => AuthRoute,
} as any)

const AuthSourcesRoute = AuthSourcesImport.update({
    id: '/sources',
    path: '/sources',
    getParentRoute: () => AuthRoute,
} as any)

const AuthRepositoriesRoute = AuthRepositoriesImport.update({
    id: '/repositories',
    path: '/repositories',
    getParentRoute: () => AuthRoute,
} as any)

const AuthPersonalTokensRoute = AuthPersonalTokensImport.update({
    id: '/personal-tokens',
    path: '/personal-tokens',
    getParentRoute: () => AuthRoute,
} as any)

const AuthDeployTokensRoute = AuthDeployTokensImport.update({
    id: '/deploy-tokens',
    path: '/deploy-tokens',
    getParentRoute: () => AuthRoute,
} as any)

const AuthPackagesIndexRoute = AuthPackagesIndexImport.update({
    id: '/packages/',
    path: '/packages/',
    getParentRoute: () => AuthRoute,
} as any)

const AuthPackagesPackageIdRoute = AuthPackagesPackageIdImport.update({
    id: '/packages/$packageId',
    path: '/packages/$packageId',
    getParentRoute: () => AuthRoute,
} as any)

// Populate the FileRoutesByPath interface

declare module '@tanstack/react-router' {
    interface FileRoutesByPath {
        '/_auth': {
            id: '/_auth'
            path: ''
            fullPath: ''
            preLoaderRoute: typeof AuthImport
            parentRoute: typeof rootRoute
        }
        '/login': {
            id: '/login'
            path: '/login'
            fullPath: '/login'
            preLoaderRoute: typeof LoginImport
            parentRoute: typeof rootRoute
        }
        '/_auth/deploy-tokens': {
            id: '/_auth/deploy-tokens'
            path: '/deploy-tokens'
            fullPath: '/deploy-tokens'
            preLoaderRoute: typeof AuthDeployTokensImport
            parentRoute: typeof AuthImport
        }
        '/_auth/personal-tokens': {
            id: '/_auth/personal-tokens'
            path: '/personal-tokens'
            fullPath: '/personal-tokens'
            preLoaderRoute: typeof AuthPersonalTokensImport
            parentRoute: typeof AuthImport
        }
        '/_auth/repositories': {
            id: '/_auth/repositories'
            path: '/repositories'
            fullPath: '/repositories'
            preLoaderRoute: typeof AuthRepositoriesImport
            parentRoute: typeof AuthImport
        }
        '/_auth/sources': {
            id: '/_auth/sources'
            path: '/sources'
            fullPath: '/sources'
            preLoaderRoute: typeof AuthSourcesImport
            parentRoute: typeof AuthImport
        }
        '/_auth/users': {
            id: '/_auth/users'
            path: '/users'
            fullPath: '/users'
            preLoaderRoute: typeof AuthUsersImport
            parentRoute: typeof AuthImport
        }
        '/_auth/': {
            id: '/_auth/'
            path: '/'
            fullPath: '/'
            preLoaderRoute: typeof AuthIndexImport
            parentRoute: typeof AuthImport
        }
        '/_auth/packages/$packageId': {
            id: '/_auth/packages/$packageId'
            path: '/packages/$packageId'
            fullPath: '/packages/$packageId'
            preLoaderRoute: typeof AuthPackagesPackageIdImport
            parentRoute: typeof AuthImport
        }
        '/_auth/packages/': {
            id: '/_auth/packages/'
            path: '/packages'
            fullPath: '/packages'
            preLoaderRoute: typeof AuthPackagesIndexImport
            parentRoute: typeof AuthImport
        }
    }
}

// Create and export the route tree

interface AuthRouteChildren {
    AuthDeployTokensRoute: typeof AuthDeployTokensRoute
    AuthPersonalTokensRoute: typeof AuthPersonalTokensRoute
    AuthRepositoriesRoute: typeof AuthRepositoriesRoute
    AuthSourcesRoute: typeof AuthSourcesRoute
    AuthUsersRoute: typeof AuthUsersRoute
    AuthIndexRoute: typeof AuthIndexRoute
    AuthPackagesPackageIdRoute: typeof AuthPackagesPackageIdRoute
    AuthPackagesIndexRoute: typeof AuthPackagesIndexRoute
}

const AuthRouteChildren: AuthRouteChildren = {
    AuthDeployTokensRoute: AuthDeployTokensRoute,
    AuthPersonalTokensRoute: AuthPersonalTokensRoute,
    AuthRepositoriesRoute: AuthRepositoriesRoute,
    AuthSourcesRoute: AuthSourcesRoute,
    AuthUsersRoute: AuthUsersRoute,
    AuthIndexRoute: AuthIndexRoute,
    AuthPackagesPackageIdRoute: AuthPackagesPackageIdRoute,
    AuthPackagesIndexRoute: AuthPackagesIndexRoute,
}

const AuthRouteWithChildren = AuthRoute._addFileChildren(AuthRouteChildren)

export interface FileRoutesByFullPath {
    '': typeof AuthRouteWithChildren
    '/login': typeof LoginRoute
    '/deploy-tokens': typeof AuthDeployTokensRoute
    '/personal-tokens': typeof AuthPersonalTokensRoute
    '/repositories': typeof AuthRepositoriesRoute
    '/sources': typeof AuthSourcesRoute
    '/users': typeof AuthUsersRoute
    '/': typeof AuthIndexRoute
    '/packages/$packageId': typeof AuthPackagesPackageIdRoute
    '/packages': typeof AuthPackagesIndexRoute
}

export interface FileRoutesByTo {
    '/login': typeof LoginRoute
    '/deploy-tokens': typeof AuthDeployTokensRoute
    '/personal-tokens': typeof AuthPersonalTokensRoute
    '/repositories': typeof AuthRepositoriesRoute
    '/sources': typeof AuthSourcesRoute
    '/users': typeof AuthUsersRoute
    '/': typeof AuthIndexRoute
    '/packages/$packageId': typeof AuthPackagesPackageIdRoute
    '/packages': typeof AuthPackagesIndexRoute
}

export interface FileRoutesById {
    __root__: typeof rootRoute
    '/_auth': typeof AuthRouteWithChildren
    '/login': typeof LoginRoute
    '/_auth/deploy-tokens': typeof AuthDeployTokensRoute
    '/_auth/personal-tokens': typeof AuthPersonalTokensRoute
    '/_auth/repositories': typeof AuthRepositoriesRoute
    '/_auth/sources': typeof AuthSourcesRoute
    '/_auth/users': typeof AuthUsersRoute
    '/_auth/': typeof AuthIndexRoute
    '/_auth/packages/$packageId': typeof AuthPackagesPackageIdRoute
    '/_auth/packages/': typeof AuthPackagesIndexRoute
}

export interface FileRouteTypes {
    fileRoutesByFullPath: FileRoutesByFullPath
    fullPaths:
        | ''
        | '/login'
        | '/deploy-tokens'
        | '/personal-tokens'
        | '/repositories'
        | '/sources'
        | '/users'
        | '/'
        | '/packages/$packageId'
        | '/packages'
    fileRoutesByTo: FileRoutesByTo
    to:
        | '/login'
        | '/deploy-tokens'
        | '/personal-tokens'
        | '/repositories'
        | '/sources'
        | '/users'
        | '/'
        | '/packages/$packageId'
        | '/packages'
    id:
        | '__root__'
        | '/_auth'
        | '/login'
        | '/_auth/deploy-tokens'
        | '/_auth/personal-tokens'
        | '/_auth/repositories'
        | '/_auth/sources'
        | '/_auth/users'
        | '/_auth/'
        | '/_auth/packages/$packageId'
        | '/_auth/packages/'
    fileRoutesById: FileRoutesById
}

export interface RootRouteChildren {
    AuthRoute: typeof AuthRouteWithChildren
    LoginRoute: typeof LoginRoute
}

const rootRouteChildren: RootRouteChildren = {
    AuthRoute: AuthRouteWithChildren,
    LoginRoute: LoginRoute,
}

export const routeTree = rootRoute._addFileChildren(rootRouteChildren)._addFileTypes<FileRouteTypes>()

/* ROUTE_MANIFEST_START
{
  "routes": {
    "__root__": {
      "filePath": "__root.tsx",
      "children": [
        "/_auth",
        "/login"
      ]
    },
    "/_auth": {
      "filePath": "_auth.tsx",
      "children": [
        "/_auth/deploy-tokens",
        "/_auth/personal-tokens",
        "/_auth/repositories",
        "/_auth/sources",
        "/_auth/users",
        "/_auth/",
        "/_auth/packages/$packageId",
        "/_auth/packages/"
      ]
    },
    "/login": {
      "filePath": "login.tsx"
    },
    "/_auth/deploy-tokens": {
      "filePath": "_auth.deploy-tokens.tsx",
      "parent": "/_auth"
    },
    "/_auth/personal-tokens": {
      "filePath": "_auth.personal-tokens.tsx",
      "parent": "/_auth"
    },
    "/_auth/repositories": {
      "filePath": "_auth.repositories.tsx",
      "parent": "/_auth"
    },
    "/_auth/sources": {
      "filePath": "_auth.sources.tsx",
      "parent": "/_auth"
    },
    "/_auth/users": {
      "filePath": "_auth.users.tsx",
      "parent": "/_auth"
    },
    "/_auth/": {
      "filePath": "_auth.index.tsx",
      "parent": "/_auth"
    },
    "/_auth/packages/$packageId": {
      "filePath": "_auth.packages.$packageId.tsx",
      "parent": "/_auth"
    },
    "/_auth/packages/": {
      "filePath": "_auth.packages.index.tsx",
      "parent": "/_auth"
    }
  }
}
ROUTE_MANIFEST_END */
