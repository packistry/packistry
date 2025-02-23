import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { ReactNode, useState } from 'react'
import { Form } from '@/components/ui/form'
import { useUpdateUser } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { UserFormElements } from '@/components/form/user-form-elements'
import { User } from '@/api'
import { DeleteUserButton } from '@/components/button/delete-user-button'

export function EditUserDialog({ user, trigger }: { trigger?: ReactNode; user: User }) {
    const mutation = useUpdateUser()
    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            ...user,
            repositories: user.repositories?.map(({ id }) => id) || '',
            password: '',
        },
        onSuccess() {
            form.resetField('password')
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
                    <DialogTitle>Edit User</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <UserFormElements
                            form={form}
                            user={user}
                        />
                        <div className="flex justify-between">
                            <Button
                                type="submit"
                                loading={isPending}
                            >
                                Update User
                            </Button>
                            <DeleteUserButton user={user} />
                        </div>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
