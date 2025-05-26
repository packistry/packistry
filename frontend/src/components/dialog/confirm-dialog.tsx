import * as React from 'react'
import { useCallback } from 'react'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { DialogProps } from '@radix-ui/react-dialog'

export type ConfirmModalProps = {
    onConfirm: () => void
    onCancel?: () => void
    title: React.ReactNode
    description?: React.ReactNode
    loading?: boolean
    confirmText?: string
    cancelText?: string
} & DialogProps

export function ConfirmDialog({
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
    const handleCancel = useCallback(() => {
        onCancel?.()
        onOpenChange?.(false)
    }, [onCancel, onOpenChange])

    return (
        <Dialog
            open={open}
            onOpenChange={onOpenChange}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && <DialogDescription>{description}</DialogDescription>}
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
