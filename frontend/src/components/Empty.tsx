import * as React from 'react'
import { cloneElement, ReactElement, ReactNode } from 'react'

export type EmptyProps = {
    title: string
    description?: string
    button?: ReactNode
    icon?: ReactElement
    className?: string
}

export function Empty({ title, description, button, icon, className }: EmptyProps) {
    return (
        <div className={`flex flex-col items-center justify-center text-center ${className}`}>
            {icon ? cloneElement(icon, { className: 'h-10 w-10 text-muted-foreground mb-4' }) : undefined}
            <h2 className="text-lg font-semibold mb-2">{title}</h2>
            {description && <p className="text-sm text-muted-foreground mb-4">{description}</p>}
            {button}
        </div>
    )
}
