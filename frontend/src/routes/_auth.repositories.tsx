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
import { useAuth } from '@/auth'
import { REPOSITORY_CREATE } from '@/permission'
import { Heading } from '@/components/page/Heading'
import { navigateOnSort } from '@/components/paginated-table'

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
    const { can } = useAuth()
    const navigate = useNavigate()

    return (
        <>
            <Heading title="Repositories">{can(REPOSITORY_CREATE) && <AddRepositoryDialog {...dialogProps} />}</Heading>
            <SearchBar
                name="repositories"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <RepositoryCards query={query} />
            <RepositoryTable
                query={query}
                sort={search.sort}
                onSort={navigateOnSort(navigate)}
            />
        </>
    )
}

function RepositoryCards({ query }: { query: UseQueryResult<PaginatedRepository> }) {
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

    if (repositories.length === 0) {
        return <></>
    }

    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {repositories.slice(0, 3).map((repository) => (
                <RepositoryCard
                    key={repository.id}
                    repository={repository}
                />
            ))}
        </div>
    )
}
