import { Card, CardContent } from '@/components/ui/card'
import { CheckCircle2, CircleAlert, Loader2, Package } from 'lucide-react'
import { Link } from '@tanstack/react-router'
import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { Batch } from '@/api/batch'
import { formatDistance } from 'date-fns'

export type ImportBatchCardProps = { batch: Batch }

export function ImportBatchCard({ batch }: ImportBatchCardProps) {
    let color = 'bg-gray-200'

    if (batch.finishedAt !== null) {
        color = 'bg-green-500'
    }

    if (batch.failedJobs > 0) {
        color = 'bg-yellow-500'
    }

    const progress = ((batch.processedJobs + batch.failedJobs) / batch.totalJobs) * 100
    const finished = batch.finishedAt !== null || batch.totalJobs - batch.failedJobs === batch.processedJobs

    return (
        <Card
            key={batch.id}
            className="overflow-hidden"
        >
            <div
                className={`h-1 ${color}`}
                style={{ width: `${progress}%` }}
            />
            <CardContent className="p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        {finished ? (
                            batch.failedJobs === 0 ? (
                                <CheckCircle2 className="h-5 w-5 text-green-500" />
                            ) : (
                                <CircleAlert className="h-5 w-5 text-yellow-500" />
                            )
                        ) : (
                            <Loader2 className="h-5 w-5 text-primary animate-spin" />
                        )}
                        <div className="flex items-center gap-2">
                            <Package className="h-4 w-4 text-muted-foreground" />
                            {batch.package ? (
                                <Link
                                    to="/packages/$packageId"
                                    params={{ packageId: batch.package.id }}
                                    className="font-medium"
                                >
                                    {batch.package.name}
                                </Link>
                            ) : (
                                <span className="font-medium">Unknown</span>
                            )}
                            <span className="text-sm text-muted-foreground">
                                Started {formatDistance(batch.createdAt, new Date(), { addSuffix: true })}
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-right font-mediumz">
                                {batch.processedJobs}/{batch.totalJobs}
                            </span>
                        </div>
                        <Badge
                            variant={finished ? 'default' : 'outline'}
                            className="capitalize"
                        >
                            {finished ? 'Finished' : 'Pending'}
                        </Badge>
                    </div>
                </div>
            </CardContent>
        </Card>
    )
}
