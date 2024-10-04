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
    updateRepository,
    updateSource,
    updateUser,
    UserQuery,
} from '@/api'
import { useAuth } from '@/auth'

const repositoriesKey = ['repositories']
const packagesKey = ['packages']
const usersKey = ['users']
const sourcesKey = ['sources']
const deployTokenKey = ['deploy-tokens']
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

export function usePackages(query: PackageQuery) {
    return useQuery({
        queryFn: () => fetchPackages(query),
        queryKey: [...packagesKey, query],
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
        },
    })
}

export function useSourceProjects(source?: string, search?: string) {
    return useQuery({
        queryFn: () => fetchSourceProjects(source!, search),
        queryKey: [...sourcesKey, source, 'projects', search],
        enabled: !!source,
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

export function useUpdateUser() {
    const queryClient = useQueryClient()

    return useMutation({
        mutationFn: updateUser,
        onSuccess(patch) {
            queryClient.setQueriesData(
                {
                    queryKey: usersKey,
                },
                (data: undefined | Awaited<ReturnType<typeof fetchUsers>>) => {
                    if (!data) {
                        return data
                    }

                    return {
                        ...data,
                        data: data.data.map((user) => {
                            if (user.id === patch.id) {
                                return {
                                    ...user,
                                    ...patch,
                                }
                            }

                            return user
                        }),
                    }
                }
            )
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
            queryClient.invalidateQueries().then(() => {
                auth.login(user)
            })
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
