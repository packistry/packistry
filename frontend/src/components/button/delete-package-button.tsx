import { Button } from '@/components/ui/button'
import * as React from 'react'
import { useDeletePackage } from '@/api/hooks'
import { Package } from '@/api'

export function DeletePackageButton({ pkg }: { pkg: Pick<Package, 'id'> }) {
    const mutation = useDeletePackage()

    return (
        <Button
            variant="ghost"
            onClick={() => mutation.mutate(pkg.id)}
            dangerous={{
                title: 'Delete package?',
                description: 'Are you sure you want to permanently delete this package including all versions?',
                confirm: {
                    loading: mutation.isPending,
                },
            }}
        >
            Remove
        </Button>
    )
}
