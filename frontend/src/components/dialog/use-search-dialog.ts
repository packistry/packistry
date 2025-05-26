import { DialogProps } from '@radix-ui/react-dialog'
import { useNavigate } from '@tanstack/react-router'
import { useState } from 'react'

export function useSearchDialog(search: { open?: boolean }): DialogProps {
    const navigate = useNavigate()

    return {
        open: search.open,
        onOpenChange: (open: boolean) => {
            navigate({
                to: '.',
                search: {
                    open: !open ? undefined : open,
                },
            })
        },
    }
}

export function useInnerDialog({ open, onOpenChange }: DialogProps): DialogProps {
    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const setOpen = (open: boolean) => (onOpenChange ? onOpenChange(open) : setIsDialogOpen(open))

    return {
        open: open || isDialogOpen,
        onOpenChange: setOpen,
    }
}
