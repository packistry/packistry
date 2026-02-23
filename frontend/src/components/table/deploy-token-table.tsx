import { PaginatedTable, PaginatedTableProps } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { PaginatedDeployToken } from '@/api/deploy-token'
import { format } from 'date-fns'
import { TokenStatus } from '@/components/badge/token-status'
import { Link } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'
import { DeleteDeployTokenButton } from '@/components/button/delete-deploy-token-button'
import { KeyIcon } from 'lucide-react'
import { DEPLOY_TOKEN_DELETE } from '@/permission'
import { actionColumn } from '@/components/table/columns'
import { ViewDeployTokenDialog } from '@/components/dialog/view-deploy-token-dialog'

export function DeployTokenTable(
    props: Omit<PaginatedTableProps<UseQueryResult<PaginatedDeployToken>>, 'empty' | 'columns'>
) {
    return (
        <PaginatedTable
            {...props}
            empty={{
                title: 'No Deploy Tokens',
                icon: <KeyIcon />,
                description: "You haven't created any deploy tokens yet. Generate a new token to get started.",
                button: (
                    <Link
                        to="."
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Generate New Token
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
                },
                {
                    key: 'token',
                    label: 'Token',
                    head: {
                        className: 'w-[250px]',
                    },
                    render: () => '*****************',
                },
                {
                    key: 'status',
                    label: 'Status',
                    head: {
                        className: 'w-[200px]',
                    },
                    render: (token) => <TokenStatus token={token} />,
                },
                {
                    key: 'lastUsedAt',
                    label: 'Last Used at',
                    sorter: true,
                    head: {
                        className: 'w-[200px]',
                    },
                    render: (token) => (token.lastUsedAt ? format(token.lastUsedAt, 'PPP') : 'Never'),
                },
                {
                    key: 'expiresAt',
                    label: 'Expires',
                    sorter: true,
                    head: {
                        className: 'w-[200px]',
                    },
                    render: (token) => (token.expiresAt ? format(token.expiresAt, 'PPP') : 'Never'),
                },
                {
                    key: 'access',
                    label: 'Access',
                    head: {
                        className: 'w-[120px] text-right',
                    },
                    cell: {
                        className: 'text-right',
                    },
                    render: (token) => (
                        <ViewDeployTokenDialog
                            token={token}
                            trigger={<Button variant="ghost">View</Button>}
                        />
                    ),
                },
                {
                    ...actionColumn,
                    permission: DEPLOY_TOKEN_DELETE,
                    render: (token) => <DeleteDeployTokenButton token={token} />,
                },
            ]}
        />
    )
}
