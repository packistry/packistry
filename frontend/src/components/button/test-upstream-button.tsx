import { Button } from '@/components/ui/button'
import * as React from 'react'
import { toast } from 'sonner'
import { PlugZapIcon } from 'lucide-react'
import { useTestUpstream } from '@/api/hooks'
import { Upstream } from '@/api/upstream'

export function TestUpstreamButton({ upstream }: { upstream: Pick<Upstream, 'id'> }) {
    const mutation = useTestUpstream()

    return (
        <Button
            type="button"
            variant="outline"
            loading={mutation.isPending}
            onClick={() =>
                mutation.mutate(upstream.id, {
                    onSuccess(result) {
                        if (result.ok) {
                            toast.success('Connection works', { description: result.metadataUrl })
                        } else {
                            toast.error('Connection failed', { description: result.error })
                        }
                    },
                })
            }
        >
            <PlugZapIcon className="h-4 w-4 mr-2" />
            Test connection
        </Button>
    )
}
