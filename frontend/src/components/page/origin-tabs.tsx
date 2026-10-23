import * as React from 'react'
import { Button } from '@/components/ui/button'
import { PackageOrigin } from '@/api'
import { cn } from '@/lib/utils'

const labels: Record<PackageOrigin, string> = {
    local: 'Local',
    mirrored: 'Mirrored',
    all: 'All',
}

export function OriginTabs({ value, onChange }: { value: PackageOrigin; onChange: (origin: PackageOrigin) => void }) {
    return (
        <div className="inline-flex rounded-md border p-1 gap-1">
            {(Object.keys(labels) as PackageOrigin[]).map((origin) => (
                <Button
                    key={origin}
                    type="button"
                    size="sm"
                    variant="ghost"
                    className={cn('h-7', value === origin && 'bg-muted')}
                    onClick={() => onChange(origin)}
                >
                    {labels[origin]}
                </Button>
            ))}
        </div>
    )
}
