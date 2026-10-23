import { PaginatedTable, PaginatedTableProps } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { Link } from '@tanstack/react-router'
import { formatDistanceToNow } from 'date-fns'
import { CircleAlertIcon, CircleCheckIcon, CloudDownloadIcon } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { TextTooltip } from '@/components/ui/tooltip'
import { PaginatedUpstream } from '@/api/upstream'
import { actionColumn } from '@/components/table/columns'
import { UPSTREAM_CREATE, UPSTREAM_UPDATE } from '@/permission'
import { EditUpstreamDialog } from '@/components/dialog/edit-upstream-dialog'
import { UpstreamBadge } from '@/components/badge/upstream-badge'
import { useAuth } from '@/auth'

export function UpstreamTable(
    props: Omit<PaginatedTableProps<UseQueryResult<PaginatedUpstream>>, 'empty' | 'columns'>
) {
    const { can } = useAuth()

    return (
        <PaginatedTable
            {...props}
            empty={{
                title: 'No Upstreams',
                icon: <CloudDownloadIcon />,
                description:
                    'Upstreams are Composer registries (like Packagist or GitLab groups) that Packistry mirrors and caches, so all packages are served through Packistry.',
                button: can(UPSTREAM_CREATE) ? (
                    <Link
                        to="."
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Add Upstream
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
                        className: 'w-[220px]',
                    },
                    cell: {
                        className: 'font-medium',
                    },
                },
                {
                    key: 'url',
                    label: 'URL',
                    sorter: true,
                    cell: {
                        className: 'font-mono text-xs max-w-[320px] truncate',
                    },
                },
                {
                    key: 'only',
                    label: 'Filters',
                    render(upstream) {
                        if (upstream.only.length === 0 && upstream.exclude.length === 0) {
                            return <span className="text-muted-foreground">All packages</span>
                        }

                        return (
                            <div className="flex flex-wrap gap-1">
                                {upstream.only.map((pattern) => (
                                    <Badge
                                        key={`only-${pattern}`}
                                        variant="outline"
                                        className="font-mono"
                                    >
                                        {pattern}
                                    </Badge>
                                ))}
                                {upstream.exclude.map((pattern) => (
                                    <Badge
                                        key={`exclude-${pattern}`}
                                        variant="outline"
                                        className="font-mono"
                                    >
                                        not {pattern}
                                    </Badge>
                                ))}
                            </div>
                        )
                    },
                },
                {
                    key: 'repositories',
                    label: 'Repositories',
                    render(upstream) {
                        const repositories = upstream.repositories || []

                        if (repositories.length === 0) {
                            return <span className="text-muted-foreground">Not used</span>
                        }

                        return repositories.map(({ name }) => name).join(', ')
                    },
                },
                {
                    key: 'packagesCount',
                    label: 'Packages',
                    sorter: true,
                    head: {
                        className: 'w-[100px]',
                    },
                    render(upstream) {
                        if (!upstream.packagesCount) {
                            return 0
                        }

                        return (
                            <Link
                                to="/packages"
                                className="underline"
                                search={{ filters: { origin: 'mirrored', upstreamId: upstream.id } }}
                            >
                                {upstream.packagesCount}
                            </Link>
                        )
                    },
                },
                {
                    key: 'distsCount',
                    label: 'Cached dists',
                    head: {
                        className: 'w-[120px]',
                    },
                },
                {
                    key: 'private',
                    label: 'Visibility',
                    head: {
                        className: 'w-[100px]',
                    },
                    render: (upstream) => <UpstreamBadge upstream={upstream} />,
                },
                {
                    key: 'lastError',
                    label: 'Status',
                    head: {
                        className: 'w-[80px]',
                    },
                    render(upstream) {
                        if (upstream.lastError === null) {
                            return <CircleCheckIcon className="h-4 w-4 text-muted-foreground" />
                        }

                        const when = upstream.lastErrorAt
                            ? ` (${formatDistanceToNow(upstream.lastErrorAt, { addSuffix: true })})`
                            : ''

                        return (
                            <TextTooltip content={`${upstream.lastError}${when}`}>
                                <CircleAlertIcon className="h-4 w-4 text-destructive" />
                            </TextTooltip>
                        )
                    },
                },
                {
                    ...actionColumn,
                    permission: UPSTREAM_UPDATE,
                    render: (upstream) => (
                        <EditUpstreamDialog
                            upstream={upstream}
                            trigger={<Button variant="ghost">Manage</Button>}
                        />
                    ),
                },
            ]}
        />
    )
}
