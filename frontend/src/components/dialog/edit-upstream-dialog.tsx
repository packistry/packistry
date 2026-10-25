import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { ReactNode, useState } from 'react'
import { useUpdateUpstream } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { Form } from '@/components/ui/form'
import { isPackagist, UpdateUpstreamInput, Upstream } from '@/api/upstream'
import { ComposerUpstreamFormElements } from '@/components/form/composer-upstream-form-elements'
import { PackagistUpstreamFormElements } from '@/components/form/packagist-upstream-form-elements'
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
        defaultValues: formValues(upstream),
        onSuccess(updated) {
            // secrets are never sent back, start from the saved upstream with empty secret fields
            form.reset(formValues(updated))
            setIsDialogOpen(false)
        },
    })

    // start from the saved upstream every time, unsaved changes of a cancelled edit are discarded
    function onOpenChange(open: boolean) {
        if (open) {
            form.reset(formValues(upstream))
        }

        setIsDialogOpen(open)
    }

    return (
        <Dialog
            open={isDialogOpen}
            onOpenChange={onOpenChange}
        >
            <DialogTrigger asChild>{trigger ? trigger : <Button>Edit</Button>}</DialogTrigger>
            <DialogContent className="min-w-[1000px]">
                <DialogHeader>
                    <DialogTitle>
                        Edit {isPackagist(upstream) ? 'Packagist' : 'Composer repository'} upstream
                    </DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        {isPackagist(upstream) ? (
                            <PackagistUpstreamFormElements form={form} />
                        ) : (
                            <ComposerUpstreamFormElements form={form} />
                        )}
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

function formValues(upstream: Upstream): UpdateUpstreamInput {
    return {
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
    }
}
