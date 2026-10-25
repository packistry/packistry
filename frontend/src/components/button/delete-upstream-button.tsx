import { Button } from '@/components/ui/button'
import * as React from 'react'
import { useDeleteUpstream } from '@/api/hooks'
import { Upstream } from '@/api/upstream'

export function DeleteUpstreamButton({ upstream }: { upstream: Pick<Upstream, 'id'> }) {
    const mutation = useDeleteUpstream()

    return (
        <Button
            variant="ghost"
            onClick={() => mutation.mutate(upstream.id)}
            dangerous={{
                title: 'Delete Upstream?',
                description:
                    'Its cached metadata and dists are removed. Packages mirrored from it are kept (including their download history) and resolve again through the remaining upstreams.',
                confirm: {
                    loading: mutation.isPending,
                },
            }}
        >
            Remove
        </Button>
    )
}
