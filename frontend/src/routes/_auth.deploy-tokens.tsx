import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useDeployToken } from '@/api/hooks'
import { deployTokenQuery } from '@/api/deploy-token'
import { DeployTokenTable } from '@/components/table/deploy-token-table'
import { AddDeployTokenDialog } from '@/components/dialog/add-deploy-token-dialog'
import { Heading } from '@/components/page/heading'
import { z } from 'zod'
import { useSearchDialog } from '@/components/dialog/use-search-dialog'
import { navigateOnSearch, SearchBar } from '@/components/page/search-bar'
import { navigateOnSort } from '@/components/paginated-table'

export const Route = createFileRoute('/_auth/deploy-tokens')({
    validateSearch: deployTokenQuery.extend({
        open: z.boolean().optional(),
    }),
    component: TokensComponent,
})

function TokensComponent() {
    const { open, ...search } = Route.useSearch()
    const query = useDeployToken(search)
    const dialogProps = useSearchDialog({ open })
    const navigate = useNavigate()

    return (
        <>
            <Heading title="Deploy Tokens">
                <AddDeployTokenDialog {...dialogProps} />
            </Heading>
            <SearchBar
                name="deploy tokens"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <DeployTokenTable
                sort={search.sort}
                query={query}
                onSort={navigateOnSort(navigate)}
            />
        </>
    )
}
