import * as React from 'react'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { MoreVertical, RefreshCw } from 'lucide-react'
import { Package } from '@/api'
import { toast } from 'sonner'
import { useRebuildPackage } from '@/api/hooks'
import { ConfirmDialog } from '@/components/dialog/confirm-dialog'

export type PackageActionsDropdownMenuProps = {
    pkg?: Pick<Package, 'id' | 'name'>
}

export function PackageActionsDropdownMenu({ pkg }: PackageActionsDropdownMenuProps) {
    const mutation = useRebuildPackage()
    const [showConfirm, setShowConfirm] = React.useState(false)

    const handleRebuild = React.useCallback(() => {
        mutation.mutate(pkg!.id, {
            onSuccess: () => {
                toast('Package rebuild started')
                setShowConfirm(false)
            },
        })
    }, [mutation, pkg?.id])

    return (
        <>
            {pkg && (
                <ConfirmDialog
                    open={showConfirm}
                    onOpenChange={setShowConfirm}
                    onConfirm={handleRebuild}
                    title="Rebuild package?"
                    description={`Are you sure you want to rebuild ${pkg.name}? This will reimport all tags and branches.`}
                    loading={mutation.isPending}
                    confirmText="Rebuild"
                />
            )}

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                    >
                        <MoreVertical className="h-4 w-4" />
                        <span className="sr-only">Open menu</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        disabled={!pkg}
                        onClick={() => setShowConfirm(true)}
                    >
                        <RefreshCw className="mr-2 h-4 w-4" />
                        <span>Rebuild Package</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </>
    )
}
