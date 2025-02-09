import * as React from 'react'
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router'
import { usePackage, usePackageDownloads, usePackageVersions } from '@/api/hooks'
import { RepositoryCard } from '@/components/card/repository-card'
import { SourceCard } from '@/components/card/source-card'
import { LoadingRepositoryCard } from '@/components/card/loading-repository-card'
import { LoadingSourceCard } from '@/components/card/loading-source-card'
import { versionQuery } from '@/api/version'
import { VersionTable } from '@/components/table/version-table'
import { navigateOnSort } from '@/components/paginated-table'
import { navigateOnSearch, SearchBar } from '@/components/page/SearchBar'
import { DownloadsCard } from '@/components/card/downloads-card'
import { Heading } from '@/components/page/Heading'
import { Empty } from '@/components/Empty'
import { Button } from '@/components/ui/button'
import { PackageIcon } from 'lucide-react'
import { is404 } from '@/api/axios'

export const Route = createFileRoute('/_auth/packages/$packageId')({
    validateSearch: versionQuery,
    component: PackagesComponent,
})

function PackagesComponent() {
    const { packageId } = Route.useParams()
    const search = Route.useSearch()

    const navigate = useNavigate()
    const query = usePackage(packageId)
    const downloads = usePackageDownloads(packageId)
    const versions = usePackageVersions(packageId, search)

    if (is404(query)) {
        return (
            <Empty
                icon={<PackageIcon />}
                title="Package not found"
                className="mt-24"
                button={
                    <Link to="/packages">
                        <Button>Back to Packages</Button>
                    </Link>
                }
            />
        )
    }

    return (
        <>
            <Heading title={query.data?.name} />
            <DownloadsCard data={downloads.data} />
            <div className="flex gap-4">
                {query.data?.repository ? (
                    <RepositoryCard
                        className="w-1/2"
                        repository={query.data.repository}
                    />
                ) : (
                    <LoadingRepositoryCard className="w-1/2" />
                )}
                {query.data?.source ? (
                    <SourceCard
                        className="w-1/2"
                        source={query.data.source}
                    />
                ) : (
                    query.data?.source !== null && <LoadingSourceCard className="w-1/2" />
                )}
            </div>
            <SearchBar
                name="Versions"
                search={search.filters?.search}
                onSearch={navigateOnSearch(navigate)}
            />
            <VersionTable
                query={versions}
                sort={search.sort}
                onSort={navigateOnSort(navigate)}
            />
        </>
    )
}
