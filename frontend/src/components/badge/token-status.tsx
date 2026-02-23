import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { isPast } from 'date-fns'

type TokenWithExpiry = {
    expiresAt: Date | null
}

export function TokenStatus({ token }: { token: TokenWithExpiry }) {
    const expired = token.expiresAt ? isPast(token.expiresAt) : false

    return <Badge variant={expired ? 'secondary' : 'default'}>{expired ? 'Expired' : 'Active'}</Badge>
}
