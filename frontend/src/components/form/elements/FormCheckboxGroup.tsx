import * as React from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Checkbox } from '@/components/ui/checkbox'
import { FormElement } from '@/components/form/elements/element'

export type FormInputProps = {
    loading?: boolean
    options: Option[]
} & FormElement

type Option = {
    value: string
    label: string
}

export function FormCheckboxGroup({ control, label, name, options, description }: FormInputProps) {
    return (
        <FormField
            control={control}
            name={name}
            render={() => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <div className="flex space-x-4">
                        {options.map((item) => (
                            <FormField
                                key={item.value}
                                control={control}
                                name={name}
                                render={({ field }) => {
                                    return (
                                        <>
                                            <FormItem
                                                key={item.value}
                                                className="flex flex-row items-start space-x-3 space-y-0"
                                            >
                                                <FormControl>
                                                    <Checkbox
                                                        checked={field.value?.includes(item.value)}
                                                        onCheckedChange={(checked) => {
                                                            return checked
                                                                ? field.onChange([...field.value, item.value])
                                                                : field.onChange(
                                                                      field.value?.filter(
                                                                          (value: string) => value !== item.value
                                                                      )
                                                                  )
                                                        }}
                                                    />
                                                </FormControl>
                                                <FormLabel className="font-normal">{item.label}</FormLabel>
                                            </FormItem>
                                        </>
                                    )
                                }}
                            />
                        ))}
                    </div>
                    <FormDescription>{description}</FormDescription>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
