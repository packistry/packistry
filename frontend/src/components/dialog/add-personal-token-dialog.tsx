import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Info, PlusIcon } from 'lucide-react'
import * as React from 'react'
import { Form } from '@/components/ui/form'
import { useStorePersonalToken } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { toast } from 'sonner'
import { TokenFormElements } from '@/components/form/token-form-elements'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { DialogProps } from '@/components/dialog/dialog'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { PERSONAL_TOKEN_CREATE } from '@/permission'

export type AddPersonalTokenDialog = DialogProps

export function AddPersonalTokenDialog(props: AddPersonalTokenDialog) {
    const { can } = useAuth()
    const mutation = useStorePersonalToken()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            abilities: ['repository:read'],
            expiresAt: '',
        },
        onSuccess({ plainText }) {
            toast('Token has been created', {
                description: plainText,
                action: {
                    label: 'Copy',
                    onClick: () => navigator.clipboard.writeText(plainText),
                },
            })
            form.reset()
            dialogProps.onOpenChange(false)
        },
    })

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(PERSONAL_TOKEN_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Generate New Token
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Generate New Personal Token</DialogTitle>
                </DialogHeader>
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertTitle>Personal Tokens</AlertTitle>
                    <AlertDescription>
                        Use a personal token, for example, to authenticate your local machine.
                    </AlertDescription>
                </Alert>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <TokenFormElements control={form.control} />
                        <Button
                            type="submit"
                            loading={isPending}
                        >
                            Generate Token
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
