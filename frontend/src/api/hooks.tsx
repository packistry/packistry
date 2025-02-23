import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
    deleteDeployToken,
    deletePackage,
    deletePersonalToken,
    deleteRepository,
    deleteSource,
    deleteUser,
    DeployTokenQuery,
    fetchDashboard,
    fetchDeployTokens,
    fetchMe,
    fetchPackage,
    fetchPackageDownloads,
    fetchPackages,
    fetchPersonalTokens,
    fetchRepositories,
    fetchSourceProjects,
    fetchSources,
    fetchUsers,
    login,
    PackageQuery,
    PersonalTokenQuery,
    RepositoryQuery,
    storeDeployToken,
    storePackage,
    storePersonalToken,
    storeRepository,
    storeSource,
    storeUser,
    updateMe,
    updateRepository,
    updateSource,
    updateUser,
    UserQuery,
} from '@/api'
import { useAuth } from '@/auth'
import { fetchPackageVersions, VersionQuery } from '@/api/version'
import {
    AuthenticationSourceQuery,
    deleteAuthenticationSource,
    fetchAuthenticationSources,
    fetchPublicAuthenticationSources,
    storeAuthenticationSource,
    updateAuthenticationSource,
} from '@/api/authentication-source'

const repositoriesKey = ['repositories']
const packagesKey = ['packages']
const usersKey = ['users']
const sourcesKey = ['sources']
const deployTokenKey = ['deploy-tokens']
const authenticationSourceKey = ['authentication-sources']
const personalTokenKey = ['personal-tokens']

export function useRepositories(query: RepositoryQuery) {
    return useQuery({
        queryFn: () => fetchRepositories(query),
        queryKey: [...repositoriesKey, query],
    })
}

export function useStoreRepository() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: storeRepository,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: repositoriesKey,
                exact: false,
            })
        },
    })
}

export function useUpdateRepository() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: updateRepository,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: repositoriesKey,
                exact: false,
            })

            queryClient.invalidateQueries({
                queryKey: packagesKey,
            })
        },
    })
}

export function useDeleteRepository() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: deleteRepository,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: repositoriesKey,
                exact: false,
            })
        },
    })
}

export function usePackageDownloads(packageId: string | number) {
    return useQuery({
        queryFn: () => fetchPackageDownloads(packageId),
        queryKey: [...packagesKey, packageId, 'downloads'],
    })
}

export function usePackages(query: PackageQuery) {
    return useQuery({
        queryFn: () => fetchPackages(query),
        queryKey: [...packagesKey, query],
    })
}

export function usePackageVersions(packageId: string | number, query: VersionQuery) {
    return useQuery({
        queryFn: () => fetchPackageVersions(packageId, query),
        queryKey: [...packagesKey, packageId, 'versions', query],
    })
}

export function usePackage(packageId: string | number) {
    return useQuery({
        queryFn: () => fetchPackage(packageId),
        queryKey: [...packagesKey, packageId],
    })
}

export function useStorePackage() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: storePackage,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: packagesKey,
                exact: false,
            })
        },
    })
}

export function useDeletePackage() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: deletePackage,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: packagesKey,
                exact: false,
            })
        },
    })
}

export function useSources() {
    return useQuery({
        queryFn: fetchSources,
        queryKey: sourcesKey,
    })
}

export function useStoreSource() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: storeSource,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: sourcesKey,
                exact: false,
            })
        },
    })
}

export function useDeleteSource() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: deleteSource,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: sourcesKey,
                exact: false,
            })
        },
    })
}

export function useUpdateSource() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: updateSource,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: sourcesKey,
                exact: false,
            })

            queryClient.invalidateQueries({
                queryKey: packagesKey,
            })
        },
    })
}

export function useSourceProjects(source?: string, search?: string) {
    return useQuery({
        queryFn: () => fetchSourceProjects(source!, search),
        queryKey: [...sourcesKey, source, 'projects', search],
        enabled: !!source && !!search && search.length >= 3,
    })
}

export function useUsers(query: UserQuery) {
    return useQuery({
        queryFn: () => fetchUsers(query),
        queryKey: [...usersKey, query],
    })
}

export function useStoreUser() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: storeUser,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: usersKey,
                exact: false,
            })
        },
    })
}

export function useUpdateMe() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: updateMe,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: usersKey,
                exact: false,
            })
        },
    })
}

export function useUpdateUser() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: updateUser,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: usersKey,
                exact: false,
            })
        },
    })
}

export function useDeleteUser() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: deleteUser,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: usersKey,
                exact: false,
            })
        },
    })
}

export function useDashboard() {
    return useQuery({
        queryFn: fetchDashboard,
        queryKey: ['dashboard'],
    })
}

export function useLogin() {
    const queryClient = useQueryClient()
    const auth = useAuth()

    return useMutation({
        mutationFn: login,
        onSuccess(user) {
            auth.login(user)

            queryClient.clear()
        },
    })
}

export function useDeployToken(query: DeployTokenQuery) {
    return useQuery({
        queryFn: () => fetchDeployTokens(query),
        queryKey: [...deployTokenKey, query],
    })
}

export function useStoreDeployToken() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: storeDeployToken,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: deployTokenKey,
                exact: false,
            })
        },
    })
}

export function usePersonalToken(query: PersonalTokenQuery) {
    return useQuery({
        queryFn: () => fetchPersonalTokens(query),
        queryKey: [...personalTokenKey, query],
    })
}

export function useStorePersonalToken() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: storePersonalToken,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: personalTokenKey,
                exact: false,
            })
        },
    })
}

export function useDeletePersonalToken() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: deletePersonalToken,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: personalTokenKey,
                exact: false,
            })
        },
    })
}

export function useDeleteDeployToken() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: deleteDeployToken,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: deployTokenKey,
                exact: false,
            })
        },
    })
}

export function usePublicAuthenticationSources() {
    return useQuery({
        queryFn: fetchPublicAuthenticationSources,
        queryKey: ['public', 'authentication-sources'],
    })
}

export function useAuthenticationSources(query: AuthenticationSourceQuery) {
    return useQuery({
        queryFn: () => fetchAuthenticationSources(query),
        queryKey: [...authenticationSourceKey, query],
    })
}

export function useMe() {
    return useQuery({
        queryFn: fetchMe,
        queryKey: [...usersKey, 'me'],
    })
}

export function useStoreAuthenticationSource() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: storeAuthenticationSource,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: authenticationSourceKey,
                exact: false,
            })
        },
    })
}

export function useUpdateAuthenticationSource() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: updateAuthenticationSource,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: authenticationSourceKey,
                exact: false,
            })
        },
    })
}

export function useDeleteAuthenticationSource() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: deleteAuthenticationSource,
        onSuccess() {
            queryClient.invalidateQueries({
                queryKey: authenticationSourceKey,
                exact: false,
            })
        },
    })
}
