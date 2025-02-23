import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useAuthenticationSources } from '@/api/hooks'
import { Heading } from '@/components/page/heading'
import { z } from 'zod'
import { useSearchDialog } from '@/components/dialog/use-search-dialog'
import { navigateOnSearch, SearchBar } from '@/components/page/search-bar'
import { navigateOnSort } from '@/components/paginated-table'
import { AuthenticationSourceTable } from '@/components/table/authentication-source-table'
import { authenticationSourceQuery } from '@/api/authentication-source'
import { AddAuthenticationSourceDialog } from '@/components/dialog/add-authentication-source-dialog'

export const Route = createFileRoute('/_auth/authentication-sources')({
    validateSearch: authenticationSourceQuery.extend({
        open: z.boolean().optional(),
    }),
    component: AuthenticationSourceComponent,
})

function AuthenticationSourceComponent() {
    const { open, ...search } = Route.useSearch()
    const query = useAuthenticationSources(search)
    const dialogProps = useSearchDialog({ open })
    const navigate = useNavigate()

    return (
        <>
            <Heading title="Authentication Sources">
                <AddAuthenticationSourceDialog {...dialogProps} />
            </Heading>
            <SearchBar
                name="authentication sources"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <AuthenticationSourceTable
                sort={search.sort}
                query={query}
                onSort={navigateOnSort(navigate)}
            />
        </>
    )
}
