import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { Form } from '@/components/ui/form'
import { useStoreRepository } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { RepositoryFormElements } from '@/components/form/repository-form-elements'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { DialogProps } from '@/components/dialog/dialog'
import { useAuth } from '@/auth'
import { REPOSITORY_CREATE } from '@/permission'

export function AddRepositoryDialog(props: DialogProps) {
    const { can } = useAuth()
    const mutation = useStoreRepository()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            path: '',
            description: '',
            public: false,
        },
        onSuccess() {
            form.reset()
            dialogProps.onOpenChange(false)
        },
    })

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(REPOSITORY_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add Repository
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New Repository</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <RepositoryFormElements control={form.control} />
                        <Button
                            loading={isPending}
                            type="submit"
                        >
                            Add Repository
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
