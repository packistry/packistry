import { Control } from 'react-hook-form'
import { ReactNode } from 'react'

export type FormElement = {
    name: string
    description?: ReactNode
    label: string
    placeholder?: string
    disabled?: boolean
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    control: Control<any>
}
