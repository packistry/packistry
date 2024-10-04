import { Card, CardContent, CardHeader } from '@/components/ui/card'
import * as React from 'react'
import { Skeleton } from '@/components/ui/skeleton'

export function LoadingRepositoryCard() {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <Skeleton className="w-20 h-4" />
                <Skeleton className="w-5 h-5" />
            </CardHeader>
            <CardContent>
                <Skeleton className="w-40 h-4" />
                <div className="flex justify-between mt-4">
                    <Skeleton className="w-36 h-6" />
                    <Skeleton className="w-14 h-5" />
                </div>
                <Skeleton className="w-full h-10 mt-4" />
            </CardContent>
        </Card>
    )
}
