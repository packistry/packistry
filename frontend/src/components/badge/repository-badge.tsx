import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { Repository } from '@/api'

export function RepositoryBadge({ repository }: { repository: Pick<Repository, 'public' | 'syncMode'> }) {
    return (
        <div className="flex items-center gap-2">
            <Badge variant={repository.public ? 'default' : 'secondary'}>{repository.public ? 'Public' : 'Private'}</Badge>
            {repository.syncMode === 'manual' && <Badge variant="outline">Manual ZIP</Badge>}
        </div>
    )
}
