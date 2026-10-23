import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { Package } from '@/api'

export function MirroredBadge({ pkg }: { pkg: Pick<Package, 'mirrorStatus' | 'upstream'> }) {
    if (!pkg.mirrorStatus) {
        return null
    }

    if (pkg.mirrorStatus === 'gone') {
        return (
            <Badge
                variant="outline"
                className="whitespace-nowrap"
            >
                No longer available upstream
            </Badge>
        )
    }

    return (
        <Badge
            variant="secondary"
            className="whitespace-nowrap"
        >
            Mirrored{pkg.upstream ? ` from ${pkg.upstream.name}` : ''}
        </Badge>
    )
}
