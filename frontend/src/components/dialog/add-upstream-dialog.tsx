import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ArrowLeftIcon, PackageIcon, PlusIcon, ServerIcon } from 'lucide-react'
import * as React from 'react'
import { ReactNode, useState } from 'react'
import { useStoreUpstream, useUpstreams } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { useInnerDialog } from '@/components/dialog/use-search-dialog'
import { useAuth } from '@/auth'
import { UPSTREAM_CREATE } from '@/permission'
import { Form } from '@/components/ui/form'
import { ComposerUpstreamFormElements } from '@/components/form/composer-upstream-form-elements'
import { PackagistUpstreamFormElements } from '@/components/form/packagist-upstream-form-elements'
import { isPackagist, PACKAGIST_URL } from '@/api/upstream'
import { cn } from '@/lib/utils'
import { DialogProps } from '@radix-ui/react-dialog'

type Kind = 'packagist' | 'composer'

const titles: Record<Kind, string> = {
    packagist: 'Add Packagist',
    composer: 'Add Composer repository',
}

export function AddUpstreamDialog(props: DialogProps) {
    const { can } = useAuth()
    const mutation = useStoreUpstream()
    const dialogProps = useInnerDialog(props)
    const [kind, setKind] = useState<Kind | null>(null)
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
            onOpenChange(false)
        },
    })

    // every time the dialog closes it starts over at choosing the kind of upstream
    function onOpenChange(open: boolean) {
        if (!open) {
            form.reset()
            setKind(null)
        }

        dialogProps.onOpenChange?.(open)
    }

    function chooseKind(value: Kind) {
        form.setValue('url', value === 'packagist' ? PACKAGIST_URL : '')
        form.setValue('name', value === 'packagist' ? 'Packagist' : '')
        form.setValue('credentials', [])

        setKind(value)
    }

    return (
        <Dialog
            {...dialogProps}
            onOpenChange={onOpenChange}
        >
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
                    <DialogTitle>{kind === null ? 'Add Upstream' : titles[kind]}</DialogTitle>
                    {kind === null && (
                        <DialogDescription>
                            Upstreams are Composer registries that Packistry mirrors. Packages a repository does not
                            have are served from its upstreams and cached by Packistry.
                        </DialogDescription>
                    )}
                </DialogHeader>
                {kind === null ? (
                    <div className="grid grid-cols-2 gap-4">
                        <KindCard
                            icon={<PackageIcon className="h-6 w-6" />}
                            title="Packagist"
                            description="Mirror public packages from packagist.org. Optionally add a GitHub token to raise GitHub's download limit."
                            disabledReason={packagistConfigured ? 'Packagist is already added.' : undefined}
                            onClick={() => chooseKind('packagist')}
                        />
                        <KindCard
                            icon={<ServerIcon className="h-6 w-6" />}
                            title="Composer repository"
                            description="Any Composer 2 repository, such as a GitLab group registry, Private Packagist, Satis or another Packistry. Supports credentials."
                            onClick={() => chooseKind('composer')}
                        />
                    </div>
                ) : (
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
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    loading={isPending}
                                >
                                    Add Upstream
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => setKind(null)}
                                >
                                    <ArrowLeftIcon className="h-4 w-4 mr-2" />
                                    Back
                                </Button>
                            </div>
                        </form>
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    )
}

function KindCard({
    icon,
    title,
    description,
    disabledReason,
    onClick,
}: {
    icon: ReactNode
    title: string
    description: string
    disabledReason?: string
    onClick: () => void
}) {
    const disabled = typeof disabledReason !== 'undefined'

    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={cn(
                'flex flex-col gap-3 rounded-lg border p-5 text-left transition-colors',
                disabled ? 'cursor-not-allowed opacity-50' : 'hover:bg-muted hover:border-foreground/30'
            )}
        >
            <div className="text-muted-foreground">{icon}</div>
            <div className="space-y-1">
                <p className="font-semibold">{title}</p>
                <p className="text-sm text-muted-foreground">{description}</p>
                {disabled && <p className="text-sm font-medium">{disabledReason}</p>}
            </div>
        </button>
    )
}
