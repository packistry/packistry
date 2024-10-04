import { Button } from '@/components/ui/button'
import * as React from 'react'
import { useDeleteDeployToken } from '@/api/hooks'
import { DeployToken } from '@/api'

export function DeleteDeployTokenButton({ token }: { token: Pick<DeployToken, 'id'> }) {
    const mutation = useDeleteDeployToken()

    return (
        <Button
            variant="ghost"
            onClick={() => mutation.mutate(token.id)}
            dangerous={{
                title: 'Delete Deploy Token?',
                description: 'Are you sure you want to permanently delete this deploy token?',
                confirm: {
                    loading: mutation.isPending,
                },
            }}
        >
            Remove
        </Button>
    )
}
