import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { DeployToken } from '@/api/deploy-token'
import { isPast } from 'date-fns'

export function TokenStatus({ token }: { token: DeployToken }) {
    const expired = token.expiresAt ? isPast(token.expiresAt) : false

    return <Badge variant={expired ? 'secondary' : 'default'}>{expired ? 'Expired' : 'Active'}</Badge>
}
