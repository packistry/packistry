import { Button } from '@/components/ui/button'
import * as React from 'react'
import { User } from '@/api'
import { useDeleteUser } from '@/api/hooks'
import { useAuth } from '@/auth'

export function DeleteUserButton({ user }: { user: Pick<User, 'id'> }) {
    const mutation = useDeleteUser()

    const { user: authUser } = useAuth()
    return (
        <Button
            variant="ghost"
            disabled={user.id === authUser?.id}
            onClick={() => mutation.mutate(user.id)}
            dangerous={{
                title: 'Delete user?',
                description: 'Are you sure you want to permanently delete this user including all versions?',
                confirm: {
                    loading: mutation.isPending,
                },
            }}
        >
            Remove
        </Button>
    )
}
