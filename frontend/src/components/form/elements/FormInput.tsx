import * as React from 'react'
import { HTMLInputTypeAttribute } from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { FormElement } from '@/components/form/elements/element'

export type FormInputProps = {
    type?: HTMLInputTypeAttribute
} & FormElement

export function FormInput({ control, label, name, placeholder, disabled, description, type }: FormInputProps) {
    return (
        <FormField
            control={control}
            name={name}
            disabled={disabled}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <FormControl>
                        <Input
                            placeholder={placeholder}
                            type={type}
                            {...field}
                        />
                    </FormControl>
                    {description && <FormDescription>{description}</FormDescription>}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
