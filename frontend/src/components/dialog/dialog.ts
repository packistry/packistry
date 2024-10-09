import { ReactNode } from 'react'

export type DialogProps = {
    open?: boolean
    onOpen?: (open: boolean) => void
    children?: ReactNode
}
