import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { DownloadsChart } from '@/components/charts/downloads-chart'
import * as React from 'react'
import { useMemo } from 'react'
import { DownloadsPerDate } from '@/api'

export type DownloadsCardProps = {
    data?: DownloadsPerDate[]
    title?: string
    description?: string
}
export function DownloadsCard({
    data,
    title = 'Downloads',
    description = 'Showing total downloads for the last 90 days',
}: DownloadsCardProps) {
    const total = useMemo(() => {
        if (typeof data === 'undefined') {
            return undefined
        }

        return data.reduce((carry, row) => {
            carry += row.downloads

            return carry
        }, 0)
    }, [data])

    return (
        <Card>
            <CardHeader className="flex flex-col items-stretch space-y-0 border-b p-0 sm:flex-row">
                <div className="flex flex-1 flex-col justify-center gap-1 px-6 py-5 sm:py-6">
                    <CardTitle>{title || <Skeleton className="w-96 h-[24px]" />}</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </div>
                <div className="flex">
                    <button className="relative z-30 flex flex-1 flex-col justify-center gap-1 border-t px-6 py-4 text-left even:border-l data-[active=true]:bg-muted/50 sm:border-l sm:border-t-0 sm:px-8 sm:py-6">
                        <span className="text-xs text-muted-foreground">Total Downloads</span>
                        <span className="text-lg font-bold leading-none sm:text-3xl">{total?.toLocaleString()}</span>
                    </button>
                </div>
            </CardHeader>
            <CardContent className="px-2 sm:p-6">
                {typeof data === 'undefined' ? (
                    <Skeleton className="w-full h-[250px]" />
                ) : (
                    <DownloadsChart data={data} />
                )}
            </CardContent>
        </Card>
    )
}
