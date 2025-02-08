import { PaginatedTable, PaginatedTableProps } from '@/components/paginated-table'
import * as React from 'react'
import { UseQueryResult } from '@tanstack/react-query'
import { PaginatedPackage } from '@/api'
import { DeletePackageButton } from '@/components/button/delete-package-button'
import { PackageIcon } from 'lucide-react'
import { Link } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/auth'
import { PACKAGE_CREATE, PACKAGE_DELETE } from '@/permission'
import { actionColumn } from '@/components/table/columns'

export function PackageTable(props: Omit<PaginatedTableProps<UseQueryResult<PaginatedPackage>>, 'empty' | 'columns'>) {
    const { can } = useAuth()

    return (
        <PaginatedTable
            {...props}
            empty={{
                title: 'No Packages',
                icon: <PackageIcon />,
                description: can(PACKAGE_CREATE)
                    ? "You haven't created any packages yet. Create a package to get started."
                    : 'No packages are available at the moment.',
                button: can(PACKAGE_CREATE) ? (
                    <Link
                        to="."
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Add Package
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
                        className: 'w-[300px]',
                    },
                    cell: {
                        className: 'font-medium',
                    },
                    render: (record) => {
                        return (
                            <Link
                                to="/packages/$packageId"
                                className="underline"
                                params={{
                                    packageId: record.id,
                                }}
                            >
                                {record.name}
                            </Link>
                        )
                    },
                },
                {
                    key: 'description',
                    label: 'Description',
                },
                {
                    key: 'latestVersion',
                    label: 'Latest Version',
                    head: {
                        className: 'w-[400px]',
                    },
                },
                {
                    key: 'downloads',
                    label: 'Downloads',
                    head: {
                        className: 'w-[150px] text-right',
                    },
                    cell: {
                        className: 'text-right',
                    },
                    sorter: true,
                    render: (pkg) => {
                        return pkg.downloads.toLocaleString()
                    },
                },
                {
                    ...actionColumn,
                    permission: PACKAGE_DELETE,
                    render: (pkg) => <DeletePackageButton pkg={pkg} />,
                },
            ]}
        />
    )
}
