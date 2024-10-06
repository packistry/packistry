import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Info, PlusIcon } from 'lucide-react'
import * as React from 'react'
import { useStoreDeployToken } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { toast } from 'sonner'
import { DeployTokenFormElements } from '@/components/form/deploy-token-form-elements'
import { DialogProps } from '@/components/dialog/dialog'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { DEPLOY_TOKEN_CREATE } from '@/permission'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Form } from '@/components/ui/form'

export type AddDeployTokenDialog = DialogProps

export function AddDeployTokenDialog(props: AddDeployTokenDialog) {
    const { can } = useAuth()
    const mutation = useStoreDeployToken()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            abilities: ['repository:read'],
            expiresAt: '',
            repositories: [],
        },
        onSuccess({ plainText }) {
            toast('Deploy Token has been created', {
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
                {can(DEPLOY_TOKEN_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Generate New Token
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Generate New Deploy Token</DialogTitle>
                </DialogHeader>
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertTitle>Deploy Tokens</AlertTitle>
                    <AlertDescription>
                        Use a deploy token for CI, server access to repositories, or other integrations.
                    </AlertDescription>
                </Alert>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        <DeployTokenFormElements control={form.control} />
                        <Button
                            type="submit"
                            loading={isPending}
                        >
                            Generate Deploy Token
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
