import { Button } from '@/components/ui/button'
import * as React from 'react'
import { useDeletePersonalToken } from '@/api/hooks'
import { PersonalToken } from '@/api'

export function DeletePersonalTokenButton({ token }: { token: Pick<PersonalToken, 'id'> }) {
    const mutation = useDeletePersonalToken()

    return (
        <Button
            variant="ghost"
            onClick={() => mutation.mutate(token.id)}
            dangerous={{
                title: 'Delete Personal Access Token?',
                description: 'Are you sure you want to permanently delete this personal access token?',
                confirm: {
                    loading: mutation.isPending,
                },
            }}
        >
            Remove
        </Button>
    )
}
