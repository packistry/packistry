import * as React from 'react'
import { createFileRoute } from '@tanstack/react-router'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { CodeIcon, DatabaseIcon, KeyIcon, PackageIcon, UsersIcon } from 'lucide-react'
import { useDashboard } from '@/api/hooks'
import { Heading } from '@/components/page/heading'
import { DownloadsCard } from '@/components/card/downloads-card'

export const Route = createFileRoute('/_auth/')({
    component: HomeComponent,
})

function HomeComponent() {
    const query = useDashboard()

    return (
        <>
            <Heading title="Dashboard" />
            <DownloadsCard data={query.data?.downloads} />
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {typeof query.data?.repositories !== 'undefined' && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Repositories</CardTitle>
                            <DatabaseIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{query.data?.repositories}</div>
                        </CardContent>
                    </Card>
                )}

                {typeof query.data?.packages !== 'undefined' && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Packages</CardTitle>
                            <PackageIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{query.data?.packages}</div>
                        </CardContent>
                    </Card>
                )}

                {typeof query.data?.users !== 'undefined' && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Users</CardTitle>
                            <UsersIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{query.data?.users}</div>
                        </CardContent>
                    </Card>
                )}
                {typeof query.data?.tokens !== 'undefined' && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Deploy Tokens</CardTitle>
                            <KeyIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{query.data?.tokens}</div>
                        </CardContent>
                    </Card>
                )}

                {typeof query.data?.sources !== 'undefined' && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Sources</CardTitle>
                            <CodeIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{query.data?.sources}</div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    )
}
