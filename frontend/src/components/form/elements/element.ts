import { Control } from 'react-hook-form'

export type FormElement = {
    name: string
    description?: string
    label: string
    placeholder?: string
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    control: Control<any>
}
