import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { PlusIcon } from 'lucide-react'
import * as React from 'react'
import { useState } from 'react'
import { useStoreUpstream, useUpstreams } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { UPSTREAM_CREATE } from '@/permission'
import { Form } from '@/components/ui/form'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ComposerUpstreamFormElements } from '@/components/form/composer-upstream-form-elements'
import { PackagistUpstreamFormElements } from '@/components/form/packagist-upstream-form-elements'
import { isPackagist, PACKAGIST_URL } from '@/api/upstream'
import { DialogProps } from '@radix-ui/react-dialog'

type Kind = 'packagist' | 'composer'

export function AddUpstreamDialog(props: DialogProps) {
    const { can } = useAuth()
    const mutation = useStoreUpstream()
    const dialogProps = useInnerDialog(props)
    const [kind, setKind] = useState<Kind>('composer')
    const upstreams = useUpstreams({ size: 1000 }, { enabled: !!dialogProps.open })
    const packagistConfigured = (upstreams.data?.data || []).some(isPackagist)

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            name: '',
            url: '',
            only: [],
            exclude: [],
            credentials: [],
        },
        onSuccess() {
            form.reset()
            setKind('composer')
            dialogProps.onOpenChange?.(false)
        },
    })

    function selectKind(value: Kind) {
        setKind(value)

        form.setValue('url', value === 'packagist' ? PACKAGIST_URL : '')
        form.setValue('name', value === 'packagist' ? 'Packagist' : '')
        form.setValue('credentials', [])
    }

    return (
        <Dialog {...dialogProps}>
            <DialogTrigger asChild>
                {can(UPSTREAM_CREATE) && (
                    <Button>
                        <PlusIcon className="h-4 w-4 mr-2" />
                        Add Upstream
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="min-w-[1000px]">
                <DialogHeader>
                    <DialogTitle>Add Upstream</DialogTitle>
                </DialogHeader>
                <div className="space-y-2 max-w-[470px]">
                    <Label>Type</Label>
                    <Select
                        value={kind}
                        onValueChange={(value) => selectKind(value as Kind)}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                value="packagist"
                                disabled={packagistConfigured}
                            >
                                Packagist{packagistConfigured ? ' (already added)' : ''}
                            </SelectItem>
                            <SelectItem value="composer">Composer repository</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <Form {...form}>
                    <form
                        onSubmit={onSubmit}
                        className="space-y-4"
                    >
                        {kind === 'packagist' ? (
                            <PackagistUpstreamFormElements form={form} />
                        ) : (
                            <ComposerUpstreamFormElements form={form} />
                        )}
                        <Button
                            type="submit"
                            loading={isPending}
                        >
                            Add Upstream
                        </Button>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    )
}
