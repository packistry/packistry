import { useNavigate } from '@tanstack/react-router'
import { DialogProps } from '@/components/dialog/dialog'
import { useState } from 'react'

export function useSearchDialog(search: { open?: boolean }) {
    const navigate = useNavigate()

    return {
        open: search.open,
        onOpen: (open: boolean) => {
            navigate({
                to: '.',
                search: {
                    open: !open ? undefined : open,
                },
            })
        },
    }
}

export function useInnerDialog({ open, onOpen }: DialogProps) {
    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const setOpen = (open: boolean) => (onOpen ? onOpen(open) : setIsDialogOpen(open))

    return {
        open: open || isDialogOpen,
        onOpenChange: setOpen,
    }
}
