import * as React from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { FormElement } from '@/components/form/elements/element'
import { Empty, EmptyProps } from '@/components/Empty'

type Option = {
    value: string
    label: string
}

export type FormSelectProps = {
    options: Option[]
    empty?: EmptyProps
    onChange?: (value: string) => void
} & FormElement

export function FormSelect({ control, label, options, disabled, name, description, empty, onChange }: FormSelectProps) {
    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <Select
                        disabled={disabled}
                        onValueChange={(value) => {
                            field.onChange(value)

                            if (onChange) {
                                onChange(value)
                            }
                        }}
                        defaultValue={field.value}
                    >
                        <FormControl>
                            <SelectTrigger>
                                <SelectValue placeholder={`Select a ${name}`} />
                            </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                            {options.length === 0 && empty ? (
                                <Empty
                                    className="my-4 max-w-sm mx-auto"
                                    {...empty}
                                />
                            ) : (
                                options.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))
                            )}
                        </SelectContent>
                    </Select>
                    {description && <FormDescription>{description}</FormDescription>}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
