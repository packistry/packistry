import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { usePackages } from '@/api/hooks'
import { AddPackageDialog } from '@/components/dialog/add-package-dialog'
import {
    RepositoryDropdownMenu,
    RepositoryDropdownMenuProps,
} from '@/components/dropdown-menu/repository-dropdown-menu'
import { packageQuery, PackageOrigin } from '@/api'
import { OriginTabs } from '@/components/page/origin-tabs'
import { useAuth } from '@/auth'
import { UPSTREAM_READ } from '@/permission'
import { Heading } from '@/components/page/heading'
import { navigateOnSearch, SearchBar } from '@/components/page/search-bar'
import { PackageTable } from '@/components/table/package-table'
import { z } from 'zod'
import { useSearchDialog } from '@/components/dialog/use-search-dialog'
import { navigateOnSort } from '@/components/paginated-table'

export const Route = createFileRoute('/_auth/packages/')({
    validateSearch: packageQuery.extend({
        open: z.boolean().optional(),
    }),
    component: PackagesComponent,
})

function PackagesComponent() {
    const { open, ...search } = Route.useSearch()
    const { can } = useAuth()
    const query = usePackages({ ...search, include: ['repository', 'upstream'] })
    const origin = search.filters?.origin || 'local'
    const dialogProps = useSearchDialog({ open })
    const navigate = useNavigate()

    const onRepoSelected: RepositoryDropdownMenuProps['onRepoSelect'] = (repo) => {
        navigate({
            to: '.',
            search: (prev) => ({
                ...prev,
                page: 1,
                filters: { ...prev.filters, repositoryId: repo?.id },
            }),
        })
    }

    const onOriginSelected = (origin: PackageOrigin) => {
        navigate({
            to: '.',
            search: (prev) => ({
                ...prev,
                page: 1,
                filters:
                    origin === 'local'
                        ? { ...prev.filters, origin: undefined, upstreamId: undefined }
                        : { ...prev.filters, origin },
            }),
        })
    }

    return (
        <>
            <Heading title="Packages">
                <div className="flex items-center space-x-4">
                    <AddPackageDialog {...dialogProps} />
                    <RepositoryDropdownMenu
                        selected={search.filters?.repositoryId}
                        onRepoSelect={onRepoSelected}
                    />
                </div>
            </Heading>
            <div className="flex items-center gap-4">
                <div className="grow">
                    <SearchBar
                        name="packages"
                        search={search.filters?.search}
                        onSearch={navigateOnSearch(navigate)}
                    />
                </div>
                {can(UPSTREAM_READ) && (
                    <OriginTabs
                        value={origin}
                        onChange={onOriginSelected}
                    />
                )}
            </div>
            <PackageTable
                sort={search.sort}
                query={query}
                onSort={navigateOnSort(navigate)}
            />
        </>
    )
}
