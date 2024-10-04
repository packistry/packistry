import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { FormRepositorySelect } from '@/components/form/elements/FormRepositorySelect'
import { FormSourceSelect } from '@/components/form/elements/FormSourceSelect'
import { FormSourceProjectCheckboxGroup } from '@/components/form/elements/FormSourceProjectCheckboxGroup'
import { FormSwitch } from '@/components/form/elements/FormSwitch'
import { Form } from '@/components/ui/form'
import { useStorePackage } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { DialogProps } from '@/components/dialog/dialog'
import { toast } from 'sonner'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { PACKAGE_CREATE } from '@/permission'

export type AddPackageDialogProps = DialogProps
export function AddPackageDialog(props: AddPackageDialogProps) {
    const { can } = useAuth()
    const mutation = useStorePackage()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            repository: '',
            source: '',
            projects: [],
            webhook: true,
        },
        onSuccess() {
            toast('Package import has been started')

            form.reset()
            dialogProps.onOpenChange(false)
        },
    })

    const source = form.watch('source')

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(PACKAGE_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add Package
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New Package</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <FormRepositorySelect control={form.control} />
                        <FormSourceSelect control={form.control} />
                        {source && (
                            <>
                                <FormSourceProjectCheckboxGroup
                                    source={source}
                                    control={form.control}
                                />
                                <FormSwitch
                                    label="Webhook"
                                    description="Automatically synchronize new pushes for tags and branches with webhooks."
                                    name="webhook"
                                    control={form.control}
                                />
                            </>
                        )}
                        <Button
                            type="submit"
                            loading={isPending}
                        >
                            Add package
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
