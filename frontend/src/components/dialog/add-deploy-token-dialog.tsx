import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ClipboardIcon, Info, PlusIcon } from 'lucide-react'
import * as React from 'react'
import { useStoreDeployToken } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { toast } from 'sonner'
import { DeployTokenFormElements } from '@/components/form/deploy-token-form-elements'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { DEPLOY_TOKEN_CREATE } from '@/permission'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Form } from '@/components/ui/form'
import { DialogProps } from '@radix-ui/react-dialog'

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
                action: (
                    <div className="flex gap-2 flex-col items-start">
                        <Button
                            size="xs"
                            onClick={() => navigator.clipboard.writeText(plainText)}
                        >
                            <ClipboardIcon size={15} />
                        </Button>
                        <Button
                            size="xs"
                            onClick={() =>
                                navigator.clipboard.writeText(
                                    `composer config bearer.${window.location.host} "${plainText}"`
                                )
                            }
                        >
                            <ClipboardIcon
                                size={15}
                                className="mr-1"
                            />
                            command
                        </Button>
                    </div>
                ),
            })
            form.reset()
            dialogProps.onOpenChange?.(false)
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
            <DialogContent className="min-w-[1000px]">
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
                        <DeployTokenFormElements form={form} />
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
