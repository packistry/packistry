import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { FormRepositorySelect } from '@/components/form/elements/form-repository-select'
import { FormSourceSelect } from '@/components/form/elements/form-source-select'
import { FormSourceProjectCheckboxGroup } from '@/components/form/elements/form-source-project-checkbox-group'
import { FormSwitch } from '@/components/form/elements/form-switch'
import { Form } from '@/components/ui/form'
import { useStorePackage } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { toast } from 'sonner'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { PACKAGE_CREATE } from '@/permission'
import { DialogProps } from '@radix-ui/react-dialog'
import { isValidationError } from '@/api'
import { addValidationToForm } from '@/hooks/useForm'
import { api } from '@/api/axios'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Label } from '@/components/ui/label'

type PackageMode = 'source' | 'upload'

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
            setMode('source')
            dialogProps.onOpenChange?.(false)
        },
    })

    const source = form.watch('source')
    const repositoryId = form.watch('repository')

    const [mode, setMode] = React.useState<PackageMode>('source')
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
            setMode('source')
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
                        onSubmit={mode === 'upload' ? (event) => event.preventDefault() : onSubmit}
                        className="space-y-4"
                    >
                        <div className="space-y-2">
                            <Label>Method</Label>
                            <RadioGroup
                                value={mode}
                                onValueChange={(value) => setMode(value as PackageMode)}
                                className="flex gap-4"
                            >
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem
                                        value="source"
                                        id="mode-source"
                                    />
                                    <Label
                                        htmlFor="mode-source"
                                        className="cursor-pointer"
                                    >
                                        From Source
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem
                                        value="upload"
                                        id="mode-upload"
                                    />
                                    <Label
                                        htmlFor="mode-upload"
                                        className="cursor-pointer"
                                    >
                                        Upload ZIP
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>
                        <FormRepositorySelect
                            description="Select the repository where this package will be added."
                            control={form.control}
                        />
                        {mode === 'source' && (
                            <>
                                <FormSourceSelect
                                    description="Choose the source from which to add a package."
                                    control={form.control}
                                />
                                {source && (
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
                                <Button
                                    type="submit"
                                    loading={isPending}
                                >
                                    Add package
                                </Button>
                            </>
                        )}
                        {mode === 'upload' && (
                            <div className="space-y-2">
                                <Label>ZIP Archive</Label>
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
                        )}
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
