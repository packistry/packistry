import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { Upstream } from '@/api/upstream'

export function UpstreamBadge({ upstream }: { upstream: Pick<Upstream, 'private'> }) {
    return <Badge variant={upstream.private ? 'secondary' : 'default'}>{upstream.private ? 'Private' : 'Public'}</Badge>
}
