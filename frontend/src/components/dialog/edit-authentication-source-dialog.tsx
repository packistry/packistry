import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { ReactNode, useState } from 'react'
import { useUpdateAuthenticationSource } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { AuthenticationSourceFormElements } from '@/components/form/authentication-source-form-elements'
import { Form } from '@/components/ui/form'
import { AuthenticationSource } from '@/api/authentication-source'
import { DeleteAuthenticationSourceButton } from '@/components/button/delete-authentication-source-button'
import { DialogProps } from '@radix-ui/react-dialog'

export type EditAuthenticationSourceDialogProps = {
    authenticationSource: AuthenticationSource
    trigger?: ReactNode
} & DialogProps

export function EditAuthenticationSourceDialog({ authenticationSource, trigger }: EditAuthenticationSourceDialogProps) {
    const mutation = useUpdateAuthenticationSource()
    const [isDialogOpen, setIsDialogOpen] = useState(false)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            ...authenticationSource,
            defaultUserRepositories: authenticationSource.repositories?.map(({ id }) => id) || [],
            defaultUserPackages: authenticationSource.packages?.map(({ id }) => id) || [],
            clientSecret: '',
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
            <DialogContent className="min-w-[1000px]">
                <DialogHeader>
                    <DialogTitle>Edit Authentication Source</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <AuthenticationSourceFormElements
                            form={form}
                            authenticationSource={authenticationSource}
                        />
                        <div className="flex justify-between">
                            <Button
                                loading={isPending}
                                type="submit"
                            >
                                Update Authentication Source
                            </Button>

                            <DeleteAuthenticationSourceButton source={authenticationSource} />
                        </div>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
