import { PaginatedTable, PaginatedTableProps } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { PackageIcon } from 'lucide-react'
import { PaginatedVersion } from '@/api/version'
import { format } from 'date-fns'

export function VersionTable(props: Omit<PaginatedTableProps<UseQueryResult<PaginatedVersion>>, 'empty' | 'columns'>) {
    return (
        <PaginatedTable
            {...props}
            empty={{
                title: 'No Versions',
                icon: <PackageIcon />,
                description: 'No versions available',
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
                    key: 'createdAt',
                    label: 'Created At',
                    head: {
                        className: 'w-[300px]',
                    },
                    sorter: true,
                    render: (version) => {
                        return format(version.createdAt, 'PPP')
                    },
                },
                {
                    key: 'totalDownloads',
                    label: 'Downloads',
                    head: {
                        className: 'w-[150px] text-right',
                    },
                    cell: {
                        className: 'text-right',
                    },
                    sorter: true,
                    render: (version) => {
                        return version.totalDownloads?.toLocaleString()
                    },
                },
            ]}
        />
    )
}
