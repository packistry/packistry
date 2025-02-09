import * as React from 'react'
import { ReactNode } from 'react'
import { Skeleton } from '@/components/ui/skeleton'

export function Heading({ children, title }: { children?: ReactNode; title: ReactNode }) {
    return (
        <div className="flex justify-between items-center min-h-[40px]">
            <h1 className="text-3xl font-bold">{title || <Skeleton className="w-64 h-[36px]" />}</h1>
            {children}
        </div>
    )
}
