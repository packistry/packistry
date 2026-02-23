import * as React from 'react'
import { ReactNode, useCallback, useMemo, useState } from 'react'
import { Control, FieldPath, FieldValues, useController } from 'react-hook-form'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ChevronDown, ChevronRight } from 'lucide-react'

import { fetchPackages } from '@/api'
import { useRepositories } from '@/api/hooks'
import { Repository } from '@/api/repository'
import { Package } from '@/api/package'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { cn } from '@/lib/utils'

type RepositoryPackageTreeProps<TFieldValues extends FieldValues> = {
    control: Control<TFieldValues>
    repositoriesName?: FieldPath<TFieldValues>
    packagesName?: FieldPath<TFieldValues>
    label?: string
    description?: ReactNode
}

export function RepositoryPackageTree<TFieldValues extends FieldValues>({
    control,
    repositoriesName = 'repositories' as FieldPath<TFieldValues>,
    packagesName = 'packages' as FieldPath<TFieldValues>,
    label = 'Repositories & Packages',
    description,
}: RepositoryPackageTreeProps<TFieldValues>) {
    const [searchTerm, setSearchTerm] = useState('')
    const [expandedRepositories, setExpandedRepositories] = useState<Record<string, boolean>>({})
    const [packagesByRepositoryId, setPackagesByRepositoryId] = useState<Record<string, Package[]>>({})
    const [loadingPackagesByRepositoryId, setLoadingPackagesByRepositoryId] = useState<Record<string, boolean>>({})
    const queryClient = useQueryClient()

    const repositoriesField = useController({
        control,
        name: repositoriesName,
    })

    const packagesField = useController({
        control,
        name: packagesName,
    })

    const repositoriesQuery = useRepositories({
        size: 1000,
        sort: 'name',
        filters: { public: false },
    })

    const selectedRepositoryIds = useMemo(
        () =>
            new Set<string>(
                ((repositoriesField.field.value as string[] | undefined) || []).map((value) => String(value))
            ),
        [repositoriesField.field.value]
    )
    const selectedPackageIds = useMemo(
        () =>
            new Set<string>(((packagesField.field.value as string[] | undefined) || []).map((value) => String(value))),
        [packagesField.field.value]
    )

    const repositories = repositoriesQuery.data?.data || []
    const normalizedSearchTerm = searchTerm.trim().toLowerCase()
    const isSearchActive = normalizedSearchTerm.length > 0

    const searchedPackagesQuery = useQuery({
        queryKey: ['repository-packages-search', normalizedSearchTerm],
        queryFn: () =>
            fetchPackages({
                size: 1000,
                include: ['repository'],
                filters: { search: normalizedSearchTerm },
            }),
        enabled: isSearchActive,
    })

    const searchedPackagesByRepositoryId = useMemo(() => {
        const grouped: Record<string, Package[]> = {}

        for (const pkg of searchedPackagesQuery.data?.data || []) {
            const repositoryId = String(pkg.repositoryId)
            grouped[repositoryId] = grouped[repositoryId] || []
            grouped[repositoryId].push(pkg)
        }

        return grouped
    }, [searchedPackagesQuery.data?.data])

    const filteredRepositories = useMemo(
        () =>
            repositories.filter((repository) => {
                if (!isSearchActive) {
                    return true
                }

                if (repository.name.toLowerCase().includes(normalizedSearchTerm)) {
                    return true
                }

                return (searchedPackagesByRepositoryId[repository.id] || []).length > 0
            }),
        [isSearchActive, normalizedSearchTerm, repositories, searchedPackagesByRepositoryId]
    )

    const loadRepositoryPackages = useCallback(
        async (repositoryId: string) => {
            if (packagesByRepositoryId[repositoryId] || loadingPackagesByRepositoryId[repositoryId]) {
                return
            }

            setLoadingPackagesByRepositoryId((previous) => ({
                ...previous,
                [repositoryId]: true,
            }))

            try {
                const response = await queryClient.fetchQuery({
                    queryKey: ['repository-packages', repositoryId],
                    queryFn: () =>
                        fetchPackages({
                            size: 1000,
                            include: ['repository'],
                            filters: {
                                repositoryId,
                            },
                        }),
                })

                setPackagesByRepositoryId((previous) => ({
                    ...previous,
                    [repositoryId]: response.data || [],
                }))
            } finally {
                setLoadingPackagesByRepositoryId((previous) => ({
                    ...previous,
                    [repositoryId]: false,
                }))
            }
        },
        [loadingPackagesByRepositoryId, packagesByRepositoryId, queryClient]
    )

    const toggleExpandedRepository = useCallback(
        (repositoryId: string) => {
            setExpandedRepositories((previous) => {
                const nextExpanded = !previous[repositoryId]

                if (nextExpanded && !isSearchActive) {
                    void loadRepositoryPackages(repositoryId)
                }

                return {
                    ...previous,
                    [repositoryId]: nextExpanded,
                }
            })
        },
        [isSearchActive, loadRepositoryPackages]
    )

    const clearSelectedPackagesForRepository = useCallback(
        async (repositoryId: string) => {
            const cachedPackages = packagesByRepositoryId[repositoryId]

            const repositoryPackages =
                cachedPackages ||
                (
                    await queryClient.fetchQuery({
                        queryKey: ['repository-packages', repositoryId],
                        queryFn: () =>
                            fetchPackages({
                                size: 1000,
                                include: ['repository'],
                                filters: {
                                    repositoryId,
                                },
                            }),
                    })
                ).data ||
                []

            const repositoryPackageIds = new Set(repositoryPackages.map((pkg) => pkg.id))
            const previousPackages: string[] = (packagesField.field.value as string[] | undefined) || []

            packagesField.field.onChange(previousPackages.filter((id) => !repositoryPackageIds.has(id)))
        },
        [packagesByRepositoryId, packagesField.field, queryClient]
    )

    function toggleRepository(repositoryId: string, checked: boolean) {
        const previousRepositories: string[] = (repositoriesField.field.value as string[] | undefined) || []

        if (checked) {
            repositoriesField.field.onChange(Array.from(new Set([...previousRepositories, repositoryId])))
            void clearSelectedPackagesForRepository(repositoryId)
            return
        }

        repositoriesField.field.onChange(previousRepositories.filter((value) => value !== repositoryId))
    }

    function togglePackage(packageId: string, checked: boolean) {
        const previousValues: string[] = (packagesField.field.value as string[] | undefined) || []

        if (checked) {
            packagesField.field.onChange(Array.from(new Set([...previousValues, packageId])))
            return
        }

        packagesField.field.onChange(previousValues.filter((value) => value !== packageId))
    }

    return (
        <div className="space-y-2">
            <p className="text-base font-medium">{label}</p>
            <Input
                type="search"
                placeholder="Search repositories..."
                value={searchTerm}
                onChange={(event) => setSearchTerm(event.target.value)}
            />
            <ScrollArea className="h-[30vh] rounded-md border">
                <div className="p-3 space-y-1">
                    {repositoriesQuery.isLoading && (
                        <div className="space-y-3 py-2">
                            {[0, 1, 2, 3, 4].map((index) => (
                                <Skeleton
                                    key={index}
                                    className="h-5 w-full rounded-full"
                                />
                            ))}
                        </div>
                    )}
                    {!repositoriesQuery.isLoading &&
                        filteredRepositories.map((repository) => (
                            <RepositoryRow
                                key={repository.id}
                                repository={repository}
                                expanded={expandedRepositories[repository.id]}
                                selectedRepository={selectedRepositoryIds.has(repository.id)}
                                selectedPackageIds={selectedPackageIds}
                                repositoryPackages={
                                    isSearchActive
                                        ? searchedPackagesByRepositoryId[repository.id] || []
                                        : packagesByRepositoryId[repository.id] || []
                                }
                                loadingPackages={
                                    isSearchActive
                                        ? searchedPackagesQuery.isFetching
                                        : loadingPackagesByRepositoryId[repository.id]
                                }
                                searchActive={isSearchActive}
                                onToggleExpanded={() => toggleExpandedRepository(repository.id)}
                                onToggleRepository={toggleRepository}
                                onTogglePackage={togglePackage}
                            />
                        ))}
                    {!repositoriesQuery.isLoading && filteredRepositories.length === 0 && (
                        <p className="text-sm text-muted-foreground py-2">No repositories found.</p>
                    )}
                </div>
            </ScrollArea>
            {description && <p className="text-sm text-muted-foreground">{description}</p>}
        </div>
    )
}

type RepositoryRowProps = {
    repository: Repository
    expanded: boolean
    selectedRepository: boolean
    selectedPackageIds: Set<string>
    repositoryPackages: Package[]
    loadingPackages: boolean
    searchActive: boolean
    onToggleExpanded: () => void
    onToggleRepository: (repositoryId: string, checked: boolean) => void
    onTogglePackage: (packageId: string, checked: boolean) => void
}

function RepositoryRow({
    repository,
    expanded,
    selectedRepository,
    selectedPackageIds,
    repositoryPackages,
    loadingPackages,
    searchActive,
    onToggleExpanded,
    onToggleRepository,
    onTogglePackage,
}: RepositoryRowProps) {
    const selectedChildrenCount = repositoryPackages.filter((pkg) => selectedPackageIds.has(pkg.id)).length
    const repositoryCheckedState: boolean | 'indeterminate' = selectedRepository
        ? true
        : selectedChildrenCount > 0
          ? 'indeterminate'
          : false

    return (
        <div className="space-y-1">
            <div className="flex items-center gap-2 rounded-md py-1">
                <button
                    type="button"
                    onClick={onToggleExpanded}
                    className="h-5 w-5 inline-flex items-center justify-center text-muted-foreground hover:text-foreground"
                >
                    {expanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                </button>
                <Checkbox
                    checked={repositoryCheckedState}
                    onCheckedChange={(checked) => onToggleRepository(repository.id, !!checked)}
                />
                <button
                    type="button"
                    onClick={onToggleExpanded}
                    className="text-sm text-left hover:underline"
                >
                    {repository.name}
                </button>
            </div>
            {expanded && (
                <div className="ml-7 rounded-md border-l pl-3 py-1 space-y-1">
                    {loadingPackages && (
                        <div className="space-y-2 py-1">
                            {[0, 1, 2].map((index) => (
                                <Skeleton
                                    key={index}
                                    className="h-4 w-full rounded-full"
                                />
                            ))}
                        </div>
                    )}
                    {!loadingPackages && repositoryPackages.length === 0 && (
                        <p className="text-xs text-muted-foreground py-1">
                            {searchActive ? 'No matching packages.' : 'No packages in this repository.'}
                        </p>
                    )}
                    {!loadingPackages &&
                        repositoryPackages.map((pkg) => {
                            const locked = selectedRepository
                            const checked = locked || selectedPackageIds.has(pkg.id)

                            return (
                                <div
                                    key={pkg.id}
                                    className={cn('flex items-center gap-2 py-1', locked && 'opacity-80')}
                                >
                                    <Checkbox
                                        checked={checked}
                                        disabled={locked}
                                        onCheckedChange={(value) => onTogglePackage(pkg.id, !!value)}
                                    />
                                    <span className="text-sm">{pkg.name}</span>
                                </div>
                            )
                        })}
                </div>
            )}
            <Separator className="my-1" />
        </div>
    )
}
