import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { z } from 'zod'
import { useUpstreams } from '@/api/hooks'
import { upstreamQuery } from '@/api/upstream'
import { Heading } from '@/components/page/heading'
import { useSearchDialog } from '@/components/dialog/use-search-dialog'
import { navigateOnSearch, SearchBar } from '@/components/page/search-bar'
import { navigateOnSort } from '@/components/paginated-table'
import { UpstreamTable } from '@/components/table/upstream-table'
import { AddUpstreamDialog } from '@/components/dialog/add-upstream-dialog'

export const Route = createFileRoute('/_auth/upstreams')({
    validateSearch: upstreamQuery.extend({
        open: z.boolean().optional(),
    }),
    component: UpstreamsComponent,
})

function UpstreamsComponent() {
    const { open, ...search } = Route.useSearch()
    const query = useUpstreams(search)
    const dialogProps = useSearchDialog({ open })
    const navigate = useNavigate()

    return (
        <>
            <Heading title="Upstreams">
                <AddUpstreamDialog {...dialogProps} />
            </Heading>
            <SearchBar
                name="upstreams"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <UpstreamTable
                sort={search.sort}
                query={query}
                onSort={navigateOnSort(navigate)}
            />
        </>
    )
}
