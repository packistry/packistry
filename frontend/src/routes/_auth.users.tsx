import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useUsers } from '@/api/hooks'
import { AddUserDialog } from '@/components/dialog/add-user-dialog'
import { UserTable } from '@/components/table/user-table'
import { Heading } from '@/components/page/Heading'
import { navigateOnSearch, SearchBar } from '@/components/page/SearchBar'
import { userQuery } from '@/api'

export const Route = createFileRoute('/_auth/users')({
    validateSearch: userQuery,
    component: UsersComponent,
})

function UsersComponent() {
    const search = Route.useSearch()
    const query = useUsers(search)
    const navigate = useNavigate()

    return (
        <>
            <Heading title="Users">
                <AddUserDialog />
            </Heading>
            <SearchBar
                name="users"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <UserTable query={query} />
        </>
    )
}
