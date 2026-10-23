import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { useStoreUpstream } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { UPSTREAM_CREATE } from '@/permission'
import { Form } from '@/components/ui/form'
import { UpstreamFormElements } from '@/components/form/upstream-form-elements'
import { DialogProps } from '@radix-ui/react-dialog'

export function AddUpstreamDialog(props: DialogProps) {
    const { can } = useAuth()
    const mutation = useStoreUpstream()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            url: '',
            only: [],
            exclude: [],
            credentials: [],
        },
        onSuccess() {
            form.reset()
            dialogProps.onOpenChange?.(false)
        },
    })

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(UPSTREAM_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add Upstream
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="min-w-[1000px]">
                <DialogHeader>
                    <DialogTitle>Add Upstream</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <UpstreamFormElements
                            form={form}
                            withPresets
                        />
                        <Button
                            type="submit"
                            loading={isPending}
                        >
                            Add Upstream
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
