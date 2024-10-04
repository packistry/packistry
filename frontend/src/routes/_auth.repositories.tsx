import { createFileRoute, useNavigate } from '@tanstack/react-router'
import * as React from 'react'
import { useRepositories } from '@/api/hooks'
import { AddRepositoryDialog } from '@/components/dialog/add-repository-dialog'
import { RepositoryCard } from '@/components/card/repository-card'
import { LoadingRepositoryCard } from '@/components/card/loading-repository-card'
import { PaginatedRepository, repositoryQuery } from '@/api'
import { RepositoryTable } from '@/components/table/repository-table'
import { navigateOnSearch, SearchBar } from '@/components/page/SearchBar'
import { UseQueryResult } from '@tanstack/react-query'
import { useSearchDialog } from '@/components/dialog/use-search-dialog'
import { z } from 'zod'

export const Route = createFileRoute('/_auth/repositories')({
    validateSearch: repositoryQuery.extend({
        open: z.boolean().optional(),
    }),
    component: RepositoriesComponent,
})

function RepositoriesComponent() {
    const { open, ...search } = Route.useSearch()
    const query = useRepositories(search)
    const dialogProps = useSearchDialog({ open })
    const navigate = useNavigate()

    return (
        <div className="space-y-6">
            <header className="flex justify-between items-center">
                <h1 className="text-3xl font-bold text-primary">Repositories</h1>
                <AddRepositoryDialog {...dialogProps} />
            </header>
            <SearchBar
                name="repositories"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <PageContent query={query} />
        </div>
    )
}

function PageContent({ query }: { query: UseQueryResult<PaginatedRepository> }) {
    if (query.isLoading) {
        return (
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <LoadingRepositoryCard />
                <LoadingRepositoryCard />
                <LoadingRepositoryCard />
            </div>
        )
    }

    const repositories = query.data?.data || []
    return (
        <>
            {repositories.length > 0 && (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {repositories.slice(0, 3).map((repository) => (
                        <RepositoryCard
                            key={repository.id}
                            repository={repository}
                        />
                    ))}
                </div>
            )}
            <RepositoryTable query={query} />
        </>
    )
}
