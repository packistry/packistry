import * as React from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { FormElement } from '@/components/form/elements/element'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'

type Option = {
    value: string
    label: string
}

export type FormRadioGroupProps = {
    options: Option[]
} & FormElement

export function FormRadioGroup({ control, label, options, name, description }: FormRadioGroupProps) {
    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem className="space-y-3">
                    <FormLabel>{label}</FormLabel>
                    <FormControl>
                        <RadioGroup
                            onValueChange={field.onChange}
                            defaultValue={field.value}
                            className="flex flex-col space-y-1"
                        >
                            {options.map((option) => (
                                <FormItem
                                    key={option.value}
                                    className="flex items-center space-x-3 space-y-0"
                                >
                                    <FormControl>
                                        <RadioGroupItem value={option.value} />
                                    </FormControl>
                                    <FormLabel className="font-normal">{option.label}</FormLabel>
                                </FormItem>
                            ))}
                        </RadioGroup>
                    </FormControl>
                    {description && <FormDescription>{description}</FormDescription>}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
