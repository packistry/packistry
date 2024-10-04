import { Badge } from '@/components/ui/badge'
import * as React from 'react'
import { User } from '@/api'

export function RoleBadge({ user }: { user: User }) {
    if (user.role === 'user') {
        return <Badge variant="secondary">User</Badge>
    }

    if (user.role === 'admin') {
        return <Badge variant="default">Admin</Badge>
    }

    throw new Error(`Badge not implemented for role ${user.role}`)
}
