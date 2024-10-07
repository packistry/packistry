import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { ReactElement, useState } from 'react'
import { useUpdateRepository } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { Repository } from '@/api'
import { DeleteRepositoryButton } from '@/components/button/delete-repository-button'
import { RepositoryFormElements } from '@/components/form/repository-form-elements'
import { Form } from '@/components/ui/form'

export function EditRepositoryDialog({ repository, trigger }: { trigger?: ReactElement; repository: Repository }) {
    const mutation = useUpdateRepository()
    const [isDialogOpen, setIsDialogOpen] = useState(false)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            ...repository,
            // @todo meh
            path: repository.path ? repository.path : '',
            description: repository.description ? repository.description : '',
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
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Repository</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={form.handleSubmit(onSubmit)}
                        className="space-y-4"
                    >
                        <RepositoryFormElements control={form.control} />
                        <div className="flex justify-between">
                            <Button
                                loading={isPending}
                                type="submit"
                            >
                                Update Repository
                            </Button>

                            <DeleteRepositoryButton repository={repository} />
                        </div>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
