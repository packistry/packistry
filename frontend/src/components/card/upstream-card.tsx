import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import * as React from 'react'
import { Link } from '@tanstack/react-router'
import { CloudDownloadIcon } from 'lucide-react'
import { Package } from '@/api'
import { MirroredBadge } from '@/components/badge/mirrored-badge'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/auth'
import { UPSTREAM_READ } from '@/permission'
import { cn } from '@/lib/utils'

export function UpstreamCard({
    pkg,
    className,
}: {
    pkg: Pick<Package, 'mirrorStatus' | 'upstream'>
    className?: string
}) {
    const { can } = useAuth()

    return (
        <Card className={cn(className)}>
            <CardHeader>
                <div className="flex flex-row items-center justify-between space-y-0">
                    <CardTitle className="font-semibold text-lg">{pkg.upstream?.name || 'Removed upstream'}</CardTitle>
                    <CloudDownloadIcon className="h-5 w-5 text-muted-foreground" />
                </div>
                <p className="text-xs text-muted-foreground">
                    This package is mirrored: Packistry caches its metadata and dists from the upstream and keeps
                    serving them when the upstream is unavailable.
                </p>
            </CardHeader>
            <CardContent>
                <div className="flex justify-between items-center">
                    <MirroredBadge pkg={pkg} />
                    {can(UPSTREAM_READ) && (
                        <Link to="/upstreams">
                            <Button
                                variant="outline"
                                size="sm"
                            >
                                Upstreams
                            </Button>
                        </Link>
                    )}
                </div>
            </CardContent>
        </Card>
    )
}
