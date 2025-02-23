import * as React from 'react'
import { HTMLInputTypeAttribute } from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input, InputProps } from '@/components/ui/input'
import { FormElement } from '@/components/form/elements/element'

export type FormInputProps = {
    type?: HTMLInputTypeAttribute
    onChange?: InputProps['onChange']
} & FormElement

export function FormInput({
    control,
    label,
    name,
    placeholder,
    disabled,
    description,
    type,
    onChange,
}: FormInputProps) {
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
                            onChange={(event) => {
                                field.onChange(event)

                                if (onChange) {
                                    onChange(event)
                                }
                            }}
                        />
                    </FormControl>
                    {description && <FormDescription>{description}</FormDescription>}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
