import { Button } from '@/components/ui/button'
import { PaginatedTable } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { PaginatedRepository } from '@/api'
import { EditRepositoryDialog } from '@/components/dialog/edit-repository-dialog'
import { RepositoryBadge } from '@/components/badge/repository-badge'
import { Link } from '@tanstack/react-router'
import { DatabaseIcon } from 'lucide-react'
import { REPOSITORY_UPDATE } from '@/permission'
import { actionColumn } from '@/components/table/columns'

export function RepositoryTable({ query }: { query: UseQueryResult<PaginatedRepository> }) {
    return (
        <PaginatedTable
            query={query}
            empty={{
                title: 'No Repositories',
                icon: <DatabaseIcon />,
                description: "You haven't created any repositories yet. Generate a new repository to get started.",
                button: (
                    <Link
                        to="."
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Add Repository
                        </Button>
                    </Link>
                ),
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
                    render: (repository) => repository.name || 'Root',
                },
                {
                    key: 'description',
                    label: 'Description',
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
