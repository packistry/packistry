import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { usePersonalToken } from '@/api/hooks'
import { AddPersonalTokenDialog } from '@/components/dialog/add-personal-token-dialog'
import { PersonalTokenTable } from '@/components/table/personal-token-table'
import { personalTokenQuery } from '@/api'
import { Heading } from '@/components/page/heading'
import { z } from 'zod'
import { useSearchDialog } from '@/components/dialog/use-search-dialog'
import { navigateOnSearch, SearchBar } from '@/components/page/search-bar'

export const Route = createFileRoute('/_auth/personal-tokens')({
    validateSearch: personalTokenQuery.extend({
        open: z.boolean().optional(),
    }),
    component: TokensComponent,
})

function TokensComponent() {
    const { open, ...search } = Route.useSearch()
    const query = usePersonalToken(search)
    const dialogProps = useSearchDialog({ open })
    const navigate = useNavigate()

    return (
        <>
            <Heading title="Personal Access Tokens">
                <AddPersonalTokenDialog {...dialogProps} />
            </Heading>
            <SearchBar
                name="personal access tokens"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <PersonalTokenTable query={query} />
        </>
    )
}
