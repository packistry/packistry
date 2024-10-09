import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { Form } from '@/components/ui/form'
import { useStoreUser } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { UserFormElements } from '@/components/form/user-form-elements'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { DialogProps } from '@/components/dialog/dialog'
import { useAuth } from '@/auth'
import { USER_CREATE } from '@/permission'

export function AddUserDialog(props: DialogProps) {
    const { can } = useAuth()
    const mutation = useStoreUser()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            email: '',
            role: 'user',
            repositories: [],
            password: '',
        },
        onSuccess() {
            form.reset()
            dialogProps.onOpenChange(false)
        },
    })

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(USER_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add User
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New User</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <UserFormElements form={form} />
                        <Button
                            type="submit"
                            loading={isPending}
                        >
                            Add User
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
