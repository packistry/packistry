import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { ReactNode, useState } from 'react'
import { Form } from '@/components/ui/form'
import { useUpdateSource } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { Source } from '@/api'
import { SourceFormElements } from '@/components/form/source-form-elements'
import { DeleteSourceButton } from '@/components/button/delete-source-button'
import { DialogProps } from '@radix-ui/react-dialog'

export type EditSourceDialogProps = {
    source: Source
    trigger?: ReactNode
} & DialogProps

export function EditSourceDialog({ source, trigger }: EditSourceDialogProps) {
    const mutation = useUpdateSource()
    const [isDialogOpen, setIsDialogOpen] = useState(false)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: source,
        onSuccess() {
            form.setValue('token', '')
            setIsDialogOpen(false)
        },
    })

    return (
        <Dialog
            open={isDialogOpen}
            onOpenChange={setIsDialogOpen}
        >
            <DialogTrigger asChild>
                {trigger ? (
                    trigger
                ) : (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Edit Source
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Source</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <SourceFormElements
                            disableProvider
                            form={form}
                        />
                        <div className="flex justify-between">
                            <Button
                                loading={isPending}
                                type="submit"
                            >
                                Update Source
                            </Button>

                            <DeleteSourceButton source={source} />
                        </div>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
