import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { Form } from '@/components/ui/form'
import { useUpdateMe } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { DialogProps } from '@/components/dialog/dialog'
import { useAuth } from '@/auth'
import { FormInput } from '@/components/form/elements/form-input'
import { UserIcon } from 'lucide-react'

export function EditMeDialog(props: DialogProps) {
    const { user } = useAuth()
    const mutation = useUpdateMe()
    const dialogProps = useInnerDialog(props)
    const isLocal = user?.authenticationSource === null

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            ...user!,
            currentPassword: '',
            password: '',
            passwordConfirmation: '',
        },
        onSuccess() {
            form.resetField('currentPassword')
            form.resetField('password')
            form.resetField('passwordConfirmation')
            dialogProps.onOpenChange(false)
        },
    })

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                >
                    <UserIcon />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Update Profile</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <FormInput
                            label="Name"
                            name="name"
                            disabled={!isLocal}
                            description={
                                !isLocal
                                    ? `Name is automatically updated on sign in with ${user?.authenticationSource?.name}`
                                    : 'Update your name.'
                            }
                            control={form.control}
                        />
                        <FormInput
                            label="Email"
                            name="email"
                            disabled
                            description={
                                !isLocal
                                    ? `Email is automatically updated on sign in with ${user?.authenticationSource?.name}`
                                    : 'Ask an administrator to update your email.'
                            }
                            control={form.control}
                        />
                        {isLocal && (
                            <>
                                <FormInput
                                    label="Current Password"
                                    name="currentPassword"
                                    description="Enter your current password."
                                    type="password"
                                    control={form.control}
                                />
                                <FormInput
                                    label="New Password"
                                    name="password"
                                    description="Enter your new password."
                                    type="password"
                                    control={form.control}
                                />
                                <FormInput
                                    label="Confirm New Password"
                                    name="passwordConfirmation"
                                    description="Confirm your new password."
                                    type="password"
                                    control={form.control}
                                />
                                <Button
                                    type="submit"
                                    loading={isPending}
                                >
                                    Update
                                </Button>
                            </>
                        )}
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
