import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { Repository } from '@/api'

export function RepositoryBadge({ repository }: { repository: Pick<Repository, 'public'> }) {
    return (
        <Badge variant={repository.public ? 'default' : 'secondary'}>{repository.public ? 'Public' : 'Private'}</Badge>
    )
}
