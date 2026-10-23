import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { ReactNode, useState } from 'react'
import { useUpdateUpstream } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { Form } from '@/components/ui/form'
import { Upstream } from '@/api/upstream'
import { UpstreamFormElements } from '@/components/form/upstream-form-elements'
import { DeleteUpstreamButton } from '@/components/button/delete-upstream-button'
import { TestUpstreamButton } from '@/components/button/test-upstream-button'
import { useAuth } from '@/auth'
import { UPSTREAM_DELETE } from '@/permission'
import { DialogProps } from '@radix-ui/react-dialog'

export type EditUpstreamDialogProps = {
    upstream: Upstream
    trigger?: ReactNode
} & DialogProps

export function EditUpstreamDialog({ upstream, trigger }: EditUpstreamDialogProps) {
    const { can } = useAuth()
    const mutation = useUpdateUpstream()
    const [isDialogOpen, setIsDialogOpen] = useState(false)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            id: upstream.id,
            name: upstream.name,
            url: upstream.url,
            only: upstream.only,
            exclude: upstream.exclude,
            credentials: (upstream.credentials || []).map((credential) => ({
                ...credential,
                username: credential.username || '',
                secret: '',
            })),
        },
        onSuccess() {
            setIsDialogOpen(false)
        },
    })

    return (
        <Dialog
            open={isDialogOpen}
            onOpenChange={setIsDialogOpen}
        >
            <DialogTrigger asChild>{trigger ? trigger : <Button>Edit</Button>}</DialogTrigger>
            <DialogContent className="min-w-[1000px]">
                <DialogHeader>
                    <DialogTitle>Edit Upstream</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <UpstreamFormElements form={form} />
                        <div className="flex justify-between">
                            <div className="flex gap-2">
                                <Button
                                    loading={isPending}
                                    type="submit"
                                >
                                    Update Upstream
                                </Button>
                                <TestUpstreamButton upstream={upstream} />
                            </div>
                            {can(UPSTREAM_DELETE) && <DeleteUpstreamButton upstream={upstream} />}
                        </div>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
