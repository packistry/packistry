import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { useStoreAuthenticationSource } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { DialogProps } from '@/components/dialog/dialog'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { AUTHENTICATION_SOURCE_CREATE } from '@/permission'
import { Form } from '@/components/ui/form'
import { AuthenticationSourceFormElements } from '@/components/form/authentication-source-form-elements'

export type AddAuthenticationSourceDialogProps = DialogProps

export function AddAuthenticationSourceDialog(props: AddAuthenticationSourceDialogProps) {
    const { can } = useAuth()
    const mutation = useStoreAuthenticationSource()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            provider: 'oidc',
            defaultUserRole: 'user',
            defaultUserRepositories: [],
            iconUrl: '',
            clientId: '',
            clientSecret: '',
            discoveryUrl: '',
            active: true,
        },
        onSuccess() {
            form.reset()
            dialogProps.onOpenChange(false)
        },
    })

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(AUTHENTICATION_SOURCE_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add Authentication Source
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="min-w-[1000px]">
                <DialogHeader>
                    <DialogTitle>Add Authentication Source</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <AuthenticationSourceFormElements form={form} />
                        <Button
                            type="submit"
                            loading={isPending}
                        >
                            Add Authentication Source
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
