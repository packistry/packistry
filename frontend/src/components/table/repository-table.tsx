import { Button } from '@/components/ui/button'
import { PaginatedTable } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { PaginatedRepository } from '@/api'
import { EditRepositoryDialog } from '@/components/dialog/edit-repository-dialog'
import { RepositoryBadge } from '@/components/badge/repository-badge'
import { Link } from '@tanstack/react-router'
import { DatabaseIcon } from 'lucide-react'
import { REPOSITORY_CREATE, REPOSITORY_UPDATE } from '@/permission'
import { actionColumn } from '@/components/table/columns'
import { useAuth } from '@/auth'

export function RepositoryTable({ query }: { query: UseQueryResult<PaginatedRepository> }) {
    const { can } = useAuth()

    return (
        <PaginatedTable
            query={query}
            empty={{
                title: 'No Repositories',
                icon: <DatabaseIcon />,
                description: can(REPOSITORY_CREATE)
                    ? "You haven't created any repositories yet. Create a repository to get started."
                    : 'No repositories are available at the moment.',
                button: can(REPOSITORY_CREATE) ? (
                    <Link
                        to="."
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Add Repository
                        </Button>
                    </Link>
                ) : undefined,
            }}
            columns={[
                {
                    key: 'name',
                    label: 'Name',
                    head: {
                        className: 'w-[250px]',
                    },
                    cell: {
                        className: 'font-medium',
                    },
                    render: (repository) => repository.name,
                },
                {
                    key: 'description',
                    label: 'Description',
                },
                {
                    key: 'path',
                    label: 'Path',
                },
                {
                    key: 'packagesCount',
                    label: 'Packages',
                    head: {
                        className: 'w-[100px]',
                    },
                },
                {
                    key: 'public',
                    label: 'Visibility',
                    head: {
                        className: 'w-[100px]',
                    },
                    render: (repository) => <RepositoryBadge repository={repository} />,
                },
                {
                    ...actionColumn,
                    permission: REPOSITORY_UPDATE,
                    render: (repository) => (
                        <EditRepositoryDialog
                            repository={repository}
                            trigger={<Button variant="ghost">Manage</Button>}
                        />
                    ),
                },
            ]}
        />
    )
}
