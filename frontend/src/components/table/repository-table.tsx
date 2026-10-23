import { Button } from '@/components/ui/button'
import { PaginatedTable, PaginatedTableProps } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { PaginatedRepository } from '@/api'
import { EditRepositoryDialog } from '@/components/dialog/edit-repository-dialog'
import { RepositoryBadge } from '@/components/badge/repository-badge'
import { Link } from '@tanstack/react-router'
import { DatabaseIcon } from 'lucide-react'
import { REPOSITORY_CREATE, REPOSITORY_UPDATE, UPSTREAM_READ } from '@/permission'
import { RepositoryUpstreamsDialog } from '@/components/dialog/repository-upstreams-dialog'
import { actionColumn } from '@/components/table/columns'
import { useAuth } from '@/auth'

export function RepositoryTable(
    props: Omit<PaginatedTableProps<UseQueryResult<PaginatedRepository>>, 'empty' | 'columns'>
) {
    const { can } = useAuth()

    return (
        <PaginatedTable
            {...props}
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
                    sorter: true,
                    head: {
                        className: 'w-[250px]',
                    },
                    cell: {
                        className: 'font-medium',
                    },
                    render: (repository) => (
                        <Link
                            to="/packages"
                            className="underline"
                            search={{ filters: { repositoryId: repository.id } }}
                        >
                            {repository.name}
                        </Link>
                    ),
                },
                {
                    key: 'description',
                    label: 'Description',
                },
                {
                    key: 'path',
                    label: 'Path',
                    sorter: true,
                },
                {
                    key: 'packagesCount',
                    label: 'Packages',
                    sorter: true,
                    head: {
                        className: 'w-[150px]',
                    },
                },
                {
                    key: 'upstreams',
                    label: 'Upstreams',
                    render: (repository) => {
                        const upstreams = (repository.upstreams || []).filter(({ enabled }) => enabled !== false)

                        if (upstreams.length === 0) {
                            return <span className="text-muted-foreground">-</span>
                        }

                        return upstreams.map(({ name }) => name).join(' → ')
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
                    head: {
                        className: 'text-right w-[200px]',
                    },
                    render: (repository) => (
                        <div className="flex justify-end">
                            {can(UPSTREAM_READ) && (
                                <RepositoryUpstreamsDialog
                                    repository={repository}
                                    trigger={<Button variant="ghost">Upstreams</Button>}
                                />
                            )}
                            <EditRepositoryDialog
                                repository={repository}
                                trigger={<Button variant="ghost">Manage</Button>}
                            />
                        </div>
                    ),
                },
            ]}
        />
    )
}
