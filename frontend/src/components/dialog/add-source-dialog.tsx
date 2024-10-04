import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { Form } from '@/components/ui/form'
import { useStoreSource } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { SourceFormElements } from '@/components/form/source-form-elements'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { DialogProps } from '@/components/dialog/dialog'
import { useAuth } from '@/auth'
import { SOURCE_CREATE } from '@/permission'

export function AddSourceDialog(props: DialogProps) {
    const { can } = useAuth()
    const mutation = useStoreSource()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            provider: '',
            url: '',
            token: '',
        },
        onSuccess() {
            form.reset()
            dialogProps.onOpenChange(false)
        },
    })

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(SOURCE_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add Source
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New Source</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(onSubmit)}
                        className="space-y-4"
                    >
                        <SourceFormElements control={form.control} />
                        <Button
                            loading={isPending}
                            type="submit"
                        >
                            Add Source
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
