import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { FormRepositorySelect } from '@/components/form/elements/form-repository-select'
import { FormSourceSelect } from '@/components/form/elements/form-source-select'
import { FormSourceProjectCheckboxGroup } from '@/components/form/elements/form-source-project-checkbox-group'
import { FormSwitch } from '@/components/form/elements/form-switch'
import { Form } from '@/components/ui/form'
import { useRepositories, useStorePackage } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { toast } from 'sonner'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { PACKAGE_CREATE } from '@/permission'
import { DialogProps } from '@radix-ui/react-dialog'
import { isValidationError } from '@/api'
import { addValidationToForm } from '@/hooks/useForm'
import { api } from '@/api/axios'

export type AddPackageDialogProps = DialogProps
export function AddPackageDialog(props: AddPackageDialogProps) {
    const { can } = useAuth()
    const mutation = useStorePackage()
    const dialogProps = useInnerDialog(props)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            repository: '',
            source: '',
            projects: [],
            webhook: true,
        },
        onSuccess() {
            toast('Package import has been started')

            form.reset()
            dialogProps.onOpenChange?.(false)
        },
    })

    const source = form.watch('source')
    const repositoryId = form.watch('repository')
    const repositories = useRepositories({
        size: 1000,
    })

    const repository = repositories.data?.data.find((item) => item.id === repositoryId)
    const isManualRepository = repository?.syncMode === 'manual'
    const uploadInputRef = React.useRef<HTMLInputElement>(null)

    const [isUploadPending, setIsUploadPending] = React.useState(false)
    const [uploadError, setUploadError] = React.useState<string | null>(null)

    const onUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0]

        setUploadError(null)

        if (!file || !repositoryId) {
            return
        }

        setIsUploadPending(true)

        try {
            const data = new FormData()

            data.append('file', file)

            await api.post(`/repositories/${repositoryId}/uploads`, data, {
                headers: {
                    Accept: 'application/json',
                },
                transformRequest: [(rawData) => rawData],
            })

            toast('ZIP uploaded successfully')
            form.reset()
            dialogProps.onOpenChange?.(false)
        } catch (error) {
            if (isValidationError(error)) {
                addValidationToForm(form, error)

                const errors = error.response?.data?.errors
                const message = errors?.repository?.[0] ?? errors?.repository ?? errors?.file?.[0] ?? errors?.file

                if (message) {
                    setUploadError(String(message))
                    toast.error(String(message))
                }

                return
            }

            setUploadError('ZIP upload failed. Please try again.')
            toast.error('ZIP upload failed. Please try again.')
        } finally {
            setIsUploadPending(false)
            event.target.value = ''
        }
    }

    const onSelectUpload = () => {
        uploadInputRef.current?.click()
    }

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(PACKAGE_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add Package
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add New Package</DialogTitle>
                </DialogHeader>
                <Form {...form}>
                    <form
                        onSubmit={isManualRepository ? (event) => event.preventDefault() : onSubmit}
                        className="space-y-4"
                    >
                        <FormRepositorySelect
                            description="Select the repository where this package will be added."
                            control={form.control}
                        />
                        {!isManualRepository && (
                            <FormSourceSelect
                                description="Choose the source from which to add a package."
                                control={form.control}
                            />
                        )}
                        {isManualRepository && (
                            <>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">ZIP Archive</label>
                                    <p className="text-sm text-muted-foreground">
                                        Upload a ZIP archive containing composer.json. The package name is read automatically.
                                    </p>
                                    <input
                                        ref={uploadInputRef}
                                        type="file"
                                        accept=".zip,application/zip"
                                        className="hidden"
                                        onChange={onUpload}
                                    />
                                    <Button
                                        type="button"
                                        loading={isUploadPending}
                                        onClick={onSelectUpload}
                                    >
                                        Select ZIP and Upload
                                    </Button>
                                    {uploadError && <p className="text-sm font-medium text-destructive">{uploadError}</p>}
                                </div>
                            </>
                        )}
                        {!isManualRepository && source && (
                            <>
                                <FormSourceProjectCheckboxGroup
                                    source={source}
                                    description="Select one or more projects to add as packages."
                                    control={form.control}
                                />
                                <FormSwitch
                                    label="Webhook"
                                    description="Automatically synchronize new pushes for tags and branches with webhooks."
                                    name="webhook"
                                    control={form.control}
                                />
                            </>
                        )}
                        {!isManualRepository && (
                            <Button
                                type="submit"
                                loading={isPending}
                            >
                                Add package
                            </Button>
                        )}
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
