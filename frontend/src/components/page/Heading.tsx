import * as React from 'react'
import { ReactElement } from 'react'

export function Heading({ children, title }: { children: ReactElement; title: string }) {
    return (
        <div className="flex justify-between items-center">
            <h1 className="text-3xl font-bold">{title}</h1>
            {children}
        </div>
    )
}
