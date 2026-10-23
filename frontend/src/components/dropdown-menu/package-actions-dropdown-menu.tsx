import * as React from 'react'
import axios from 'axios'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { CloudDownloadIcon, MoreVertical, RefreshCw } from 'lucide-react'
import { Package } from '@/api'
import { toast } from 'sonner'
import { useRebuildPackage, useRefreshPackage } from '@/api/hooks'
import { ConfirmDialog } from '@/components/dialog/confirm-dialog'

export type PackageActionsDropdownMenuProps = {
    pkg?: Pick<Package, 'id' | 'name' | 'mirrorStatus'>
}

export function PackageActionsDropdownMenu({ pkg }: PackageActionsDropdownMenuProps) {
    const mutation = useRebuildPackage()
    const refresh = useRefreshPackage()
    const [showConfirm, setShowConfirm] = React.useState(false)
    const mirrored = !!pkg?.mirrorStatus

    const handleRebuild = React.useCallback(() => {
        mutation.mutate(pkg!.id, {
            onSuccess: () => {
                toast('Package rebuild started')
                setShowConfirm(false)
            },
        })
    }, [mutation, pkg?.id])

    const handleRefresh = React.useCallback(() => {
        refresh.mutate(pkg!.id, {
            onSuccess: (refreshed) => {
                toast.success('Package refreshed from upstream', {
                    description: refreshed.latestVersion ? `Latest version: ${refreshed.latestVersion}` : undefined,
                })
            },
            onError: (error) => {
                const errors = axios.isAxiosError(error) ? error.response?.data?.errors : undefined
                const message = errors?.package ? String(errors.package) : 'The upstream could not be reached'

                toast.error('Refresh failed', { description: message })
            },
        })
    }, [refresh, pkg?.id])

    return (
        <>
            {pkg && !mirrored && (
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
                    {mirrored ? (
                        <DropdownMenuItem
                            disabled={!pkg || refresh.isPending}
                            onClick={handleRefresh}
                        >
                            <CloudDownloadIcon className="mr-2 h-4 w-4" />
                            <span>Refresh from upstream</span>
                        </DropdownMenuItem>
                    ) : (
                        <DropdownMenuItem
                            disabled={!pkg}
                            onClick={() => setShowConfirm(true)}
                        >
                            <RefreshCw className="mr-2 h-4 w-4" />
                            <span>Rebuild Package</span>
                        </DropdownMenuItem>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>
        </>
    )
}
