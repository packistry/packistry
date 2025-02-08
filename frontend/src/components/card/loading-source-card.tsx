import { Card, CardContent } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import * as React from 'react'
import { cn } from '@/lib/utils'

export function LoadingSourceCard({ className }: { className?: string }) {
    return (
        <Card className={cn('overflow-hidden', className)}>
            <CardContent className="p-0">
                <div className="p-4 bg-background text-primary-foreground">
                    <div className="flex justify-between items-center">
                        <Skeleton className="h-5 w-20" />
                        <Skeleton className="h-6 w-6" />
                    </div>
                    <Skeleton className="h-3 w-40 mt-4" />
                </div>
                <div className="p-4">
                    <div className="flex justify-between items-center mb-4">
                        <Skeleton className="h-5 w-20" />
                        <Skeleton className="h-3 w-28" />
                    </div>
                    <div className="flex space-x-2">
                        <Skeleton className="h-8 w-full" />
                        <Skeleton className="h-8 w-full" />
                    </div>
                </div>
            </CardContent>
        </Card>
    )
}
