import { Skeleton } from '@/components/ui/skeleton'
import { EditUserDialog } from '@/components/dialog/edit-user-dialog'
import { Button } from '@/components/ui/button'
import { PaginatedTable } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { PaginatedUser } from '@/api'
import { UserAvatar } from '@/components/avatar/user-avatar'
import { USER_UPDATE } from '@/permission'
import { actionColumn } from '@/components/table/columns'
import { RoleBadge } from '@/components/badge/role-badge'

export function UserTable({ query }: { query: UseQueryResult<PaginatedUser> }) {
    return (
        <PaginatedTable
            query={query}
            columns={[
                {
                    key: 'user',
                    label: 'User',
                    head: {
                        className: 'w-[100px]',
                    },
                    skeleton: <Skeleton className="h-10 w-10 rounded-full" />,
                    render: (user) => <UserAvatar user={user} />,
                },
                {
                    key: 'name',
                    label: 'Name',
                    cell: {
                        className: 'font-medium',
                    },
                },
                {
                    key: 'email',
                    label: 'Email',
                    head: {
                        className: 'w-[400px]',
                    },
                    render: (user) => (
                        <a
                            href={`mailto:${user.email}`}
                            rel="noreferrer"
                        >
                            {user.email}
                        </a>
                    ),
                },
                {
                    key: 'role',
                    label: 'Role',
                    head: {
                        className: 'w-[100px]',
                    },
                    render: (user) => <RoleBadge user={user} />,
                },
                {
                    ...actionColumn,
                    permission: USER_UPDATE,
                    render: (user) => (
                        <EditUserDialog
                            user={user}
                            trigger={<Button variant="ghost">Manage</Button>}
                        />
                    ),
                },
            ]}
        />
    )
}
