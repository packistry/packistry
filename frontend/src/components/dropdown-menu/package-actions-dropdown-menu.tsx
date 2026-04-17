import * as React from 'react'
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { MoreVertical, RefreshCw, Download } from 'lucide-react'
import { Package } from '@/api'
import { toast } from 'sonner'
import { useRebuildPackage, useImportMissingPackage } from '@/api/hooks'
import { ConfirmDialog } from '@/components/dialog/confirm-dialog'

export type PackageActionsDropdownMenuProps = {
    pkg?: Pick<Package, 'id' | 'name' | 'source'>
}

export function PackageActionsDropdownMenu({ pkg }: PackageActionsDropdownMenuProps) {
    const rebuildMutation = useRebuildPackage()
    const importMissingMutation = useImportMissingPackage()
    const [showConfirm, setShowConfirm] = React.useState(false)
    const [showImportMissingConfirm, setShowImportMissingConfirm] = React.useState(false)

    const handleRebuild = React.useCallback(() => {
        rebuildMutation.mutate(pkg!.id, {
            onSuccess: () => {
                toast('Package rebuild started')
                setShowConfirm(false)
            },
        })
    }, [rebuildMutation, pkg?.id])

    const handleImportMissing = React.useCallback(() => {
        importMissingMutation.mutate(pkg!.id, {
            onSuccess: () => {
                toast('Import missing versions started')
                setShowImportMissingConfirm(false)
            },
        })
    }, [importMissingMutation, pkg?.id])

    return (
        <>
            {pkg && (
                <>
                    <ConfirmDialog
                        open={showConfirm}
                        onOpenChange={setShowConfirm}
                        onConfirm={handleRebuild}
                        title="Rebuild package?"
                        description={`Are you sure you want to rebuild ${pkg.name}? This will reimport all tags and branches.`}
                        loading={rebuildMutation.isPending}
                        confirmText="Rebuild"
                    />
                    <ConfirmDialog
                        open={showImportMissingConfirm}
                        onOpenChange={setShowImportMissingConfirm}
                        onConfirm={handleImportMissing}
                        title="Import missing versions?"
                        description={`This will import only versions of ${pkg.name} that are not yet present. Existing versions will be skipped.`}
                        loading={importMissingMutation.isPending}
                        confirmText="Import Missing"
                    />
                </>
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
                        disabled={!pkg || pkg.source === null}
                        onClick={() => setShowImportMissingConfirm(true)}
                    >
                        <Download className="mr-2 h-4 w-4" />
                        <span>Import Missing</span>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        disabled={!pkg || pkg.source === null}
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
