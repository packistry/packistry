import { PaginatedTable, PaginatedTableProps } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'
import { CheckIcon, FingerprintIcon, MinusIcon } from 'lucide-react'
import { PaginatedAuthenticationSource } from '@/api/authentication-source'
import { actionColumn } from '@/components/table/columns'
import { AUTHENTICATION_SOURCE_UPDATE } from '@/permission'
import { EditAuthenticationSourceDialog } from '@/components/dialog/edit-authentication-source-dialog'

export function AuthenticationSourceTable(
    props: Omit<PaginatedTableProps<UseQueryResult<PaginatedAuthenticationSource>>, 'empty' | 'columns'>
) {
    return (
        <PaginatedTable
            {...props}
            empty={{
                title: 'No Authentication Sources',
                icon: <FingerprintIcon />,
                description:
                    "You haven't created any authentication sources yet. Create a new authentication source to get started.",
                button: (
                    <Link
                        to="."
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Add Authentication Source
                        </Button>
                    </Link>
                ),
            }}
            columns={[
                {
                    key: 'name',
                    label: 'Name',
                    sorter: true,
                    cell: {
                        className: 'font-medium',
                    },
                    render(authSource) {
                        if (authSource.iconUrl === null) {
                            return authSource.name
                        }

                        return (
                            <div className="flex items-center">
                                <img
                                    className="max-h-6 max-w-6 mr-2"
                                    src={authSource.iconUrl}
                                    alt={`${authSource.name} icon`}
                                />
                                {authSource.name}
                            </div>
                        )
                    },
                },
                {
                    key: 'callbackUrl',
                    label: 'Callback URL',
                },
                {
                    key: 'active',
                    label: 'Active',
                    sorter: true,
                    head: {
                        className: 'w-[150px]',
                    },
                    render(authSource) {
                        return authSource.active ? <CheckIcon /> : <MinusIcon />
                    },
                },
                {
                    ...actionColumn,
                    permission: AUTHENTICATION_SOURCE_UPDATE,
                    render: (authSource) => (
                        <EditAuthenticationSourceDialog
                            authenticationSource={authSource}
                            trigger={<Button variant="ghost">Manage</Button>}
                        />
                    ),
                },
            ]}
        />
    )
}
