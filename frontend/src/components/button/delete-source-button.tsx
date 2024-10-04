import { Button, ButtonProps } from '@/components/ui/button'
import * as React from 'react'
import { useDeleteSource } from '@/api/hooks'
import { Source } from '@/api'

export type DeleteSourceButtonProps = { source: Pick<Source, 'id' | 'name'> } & ButtonProps

export function DeleteSourceButton({ source, ...props }: DeleteSourceButtonProps) {
    const mutation = useDeleteSource()

    return (
        <Button
            variant="destructive"
            onClick={() => mutation.mutate(source.id)}
            dangerous={{
                title: 'Delete Source?',
                description: 'Are you sure you want to permanently delete this source?',
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
