import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import * as React from 'react'
import { User } from '@/api'

export type UserAvatarProps = {
    user: Pick<User, 'name'>
}

export function UserAvatar({ user }: UserAvatarProps) {
    return (
        <Avatar className="h-8 w-8">
            <AvatarImage src={`https://api.dicebear.com/9.x/initials/svg?seed=${user.name}`} />
            <AvatarFallback>
                {user.name
                    .split(' ')
                    .map((n) => n[0])
                    .join('')}
            </AvatarFallback>
        </Avatar>
    )
}
