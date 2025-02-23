import { Button } from '@/components/ui/button'
import * as React from 'react'
import { useDeleteAuthenticationSource } from '@/api/hooks'
import { AuthenticationSource } from '@/api/authentication-source'

export function DeleteAuthenticationSourceButton({ source }: { source: Pick<AuthenticationSource, 'id'> }) {
    const mutation = useDeleteAuthenticationSource()

    return (
        <Button
            variant="ghost"
            onClick={() => mutation.mutate(source.id)}
            dangerous={{
                title: 'Delete Authentication Source?',
                description: 'Are you sure you want to permanently delete this authentication source?',
                confirm: {
                    loading: mutation.isPending,
                },
            }}
        >
            Remove
        </Button>
    )
}
