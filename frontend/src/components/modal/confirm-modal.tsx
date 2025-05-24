import * as React from 'react'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'

export type ConfirmModalProps = {
    open: boolean
    onOpenChange: (open: boolean) => void
    onConfirm: () => void
    onCancel?: () => void
    title: React.ReactNode
    description?: React.ReactNode
    children?: React.ReactNode
    loading?: boolean
    confirmText?: string
    cancelText?: string
}

export function ConfirmModal({
    open,
    onOpenChange,
    onConfirm,
    onCancel,
    title,
    description,
    children,
    loading = false,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
}: ConfirmModalProps) {
    const handleCancel = React.useCallback(() => {
        onCancel?.()
        onOpenChange(false)
    }, [onCancel, onOpenChange])

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>

                {children}

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={handleCancel}
                    >
                        {cancelText}
                    </Button>
                    <Button
                        onClick={onConfirm}
                        loading={loading}
                    >
                        {confirmText}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
} 