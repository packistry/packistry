import { PaginatedTable } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { format } from 'date-fns'
import { TokenStatus } from '@/components/badge/token-status'
import { PaginatedPersonalToken } from '@/api'
import { DeletePersonalTokenButton } from '@/components/button/delete-personal-token-button'
import { Button } from '@/components/ui/button'
import { Link } from '@tanstack/react-router'
import { KeyRound } from 'lucide-react'
import { PERSONAL_TOKEN_DELETE } from '@/permission'
import { actionColumn } from '@/components/table/columns'

export function PersonalTokenTable({ query }: { query: UseQueryResult<PaginatedPersonalToken> }) {
    return (
        <PaginatedTable
            query={query}
            empty={{
                title: 'No Personal Access Tokens',
                icon: <KeyRound />,
                description: "You haven't created any personal access tokens yet. Generate a new token to get started.",
                button: (
                    <Link
                        to="/personal-tokens"
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
                    cell: {
                        className: 'font-medium',
                    },
                },
                {
                    key: 'token',
                    label: 'Token',
                    head: {
                        className: 'w-[200px]',
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
                    head: {
                        className: 'w-[200px]',
                    },
                    render: (token) => (token.lastUsedAt ? format(token.lastUsedAt, 'PPP') : 'Never'),
                },
                {
                    key: 'expiresAt',
                    label: 'Expires',
                    head: {
                        className: 'w-[200px]',
                    },
                    render: (token) => (token.expiresAt ? format(token.expiresAt, 'PPP') : 'Never'),
                },
                {
                    ...actionColumn,
                    permission: PERSONAL_TOKEN_DELETE,
                    render: (token) => <DeletePersonalTokenButton token={token} />,
                },
            ]}
        />
    )
}
