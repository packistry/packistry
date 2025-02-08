import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { usePackages } from '@/api/hooks'
import { AddPackageDialog } from '@/components/dialog/add-package-dialog'
import {
  RepositoryDropdownMenu,
  RepositoryDropdownMenuProps,
} from '@/components/dropdown-menu/repository-dropdown-menu'
import { packageQuery } from '@/api'
import { Heading } from '@/components/page/Heading'
import { navigateOnSearch, SearchBar } from '@/components/page/SearchBar'
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
  const query = usePackages(search)
  const dialogProps = useSearchDialog({ open })
  const navigate = useNavigate()

  const onRepoSelected: RepositoryDropdownMenuProps['onRepoSelect'] = (
    repo,
  ) => {
    navigate({
      to: '.',
      search: (prev) => ({
        ...prev,
        filters: { ...prev.filters, repositoryId: repo?.id },
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
      <SearchBar
        name="packages"
        search={search.filters?.search}
        onSearch={navigateOnSearch(navigate)}
      />
      <PackageTable
        sort={search.sort}
        query={query}
        onSort={navigateOnSort(navigate)}
      />
    </>
  )
}
