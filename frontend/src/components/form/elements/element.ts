import { Control } from 'react-hook-form'

export type FormElement = {
    name: string
    description?: string
    label: string
    placeholder?: string
    control: Control<any>
}
