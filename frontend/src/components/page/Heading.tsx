import * as React from 'react'
import { ReactNode } from 'react'

export function Heading({ children, title }: { children?: ReactNode; title: string }) {
    return (
        <div className="flex justify-between items-center min-h-[40px]">
            <h1 className="text-3xl font-bold">{title}</h1>
            {children}
        </div>
    )
}
