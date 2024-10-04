import { Button, ButtonProps } from '@/components/ui/button'
import * as React from 'react'
import { useDeleteRepository } from '@/api/hooks'
import { Repository } from '@/api'

export type DeleteRepositoryButtonProps = { repository: Pick<Repository, 'id' | 'name'> } & ButtonProps

export function DeleteRepositoryButton({ repository, ...props }: DeleteRepositoryButtonProps) {
    const mutation = useDeleteRepository()

    return (
        <Button
            variant="destructive"
            onClick={() => mutation.mutate(repository.id)}
            dangerous={{
                title: 'Delete Repository?',
                description:
                    'Are you sure you want to permanently delete this repository including all packages and versions?',
                confirm: {
                    loading: mutation.isPending,
                },
            }}
            {...props}
        >
            Remove
        </Button>
    )
}
