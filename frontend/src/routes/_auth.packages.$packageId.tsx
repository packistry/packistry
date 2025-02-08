import * as React from 'react'
import { createFileRoute } from '@tanstack/react-router'
import { packageQuery } from '@/api'
import { z } from 'zod'
import { usePackage } from '@/api/hooks'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { format } from 'date-fns'
import { RepositoryCard } from '@/components/card/repository-card'
import { SourceCard } from '@/components/card/source-card'
import { LoadingRepositoryCard } from '@/components/card/loading-repository-card'
import { LoadingSourceCard } from '@/components/card/loading-source-card'
import { Skeleton } from '@/components/ui/skeleton'

export const Route = createFileRoute('/_auth/packages/$packageId')({
    validateSearch: packageQuery.extend({
        open: z.boolean().optional(),
    }),
    component: PackagesComponent,
})

function PackagesComponent() {
    const { packageId } = Route.useParams()

    const query = usePackage(packageId)
    console.log(query.data?.versions)
    const versions = query.data?.versions || []

    return (
        <>
            <h1 className="text-3xl font-bold">{query.data?.name || <Skeleton className="w-96 h-8" />}</h1>
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
                    <LoadingSourceCard className="w-1/2" />
                )}
            </div>
            {!query.isPending && (
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="">Version</TableHead>
                            <TableHead className="w-[200px]">Created At</TableHead>
                            <TableHead className="text-right w-[100px]">Downloads</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {versions.map((version) => (
                            <TableRow key={version.id}>
                                <TableCell className="font-medium">{version.name}</TableCell>
                                <TableCell>{version.createdAt ? format(version.createdAt, 'PPP') : 'Never'}</TableCell>
                                <TableCell className="text-right">{version.downloadsCount?.toLocaleString()}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </>
    )
}
