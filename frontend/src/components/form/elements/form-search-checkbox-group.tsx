import * as React from 'react'
import { useState } from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Checkbox } from '@/components/ui/checkbox'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { Input } from '@/components/ui/input'
import { FormElement } from '@/components/form/elements/element'

export type FormSearchCheckboxGroupProps = {
    loading?: boolean
    options: Option[]
    search?: string
    onSearch?: (search: string) => void
} & FormElement

type Option = {
    value: string
    label: string
    checked?: boolean
    disabled?: boolean
}

export function FormSearchCheckboxGroup({
    control,
    label,
    name,
    options,
    placeholder,
    loading = false,
    search,
    onSearch,
    description,
}: FormSearchCheckboxGroupProps) {
    const [searchTerm, setSearchTerm] = useState('')

    const filteredOptions = onSearch
        ? options
        : options.filter((option) => option.label.toLowerCase().includes(searchTerm.toLowerCase()))

    return (
        <FormField
            control={control}
            name={name}
            render={() => (
                <FormItem>
                    <FormLabel className="text-base">{label}</FormLabel>
                    <Input
                        type="search"
                        placeholder={placeholder || 'Search...'}
                        value={search}
                        onChange={(e) => (onSearch ? onSearch(e.target.value) : setSearchTerm(e.target.value))}
                    />
                    <ScrollArea className="h-48 rounded-md border">
                        <div className="p-4">
                            {loading ? (
                                <div className="flex flex-col space-y-4">
                                    {[0, 0, 0, 0, 0, 0, 0, 0].map((_, index) => (
                                        <Skeleton
                                            key={index}
                                            className="w-full h-[20px] rounded-full"
                                        />
                                    ))}
                                </div>
                            ) : (
                                filteredOptions.map((item) => (
                                    <FormField
                                        key={item.value}
                                        control={control}
                                        name={name}
                                        render={({ field }) => {
                                            const checked =
                                                typeof item.checked === 'boolean'
                                                    ? item.checked
                                                    : field.value?.includes(item.value)

                                            return (
                                                <>
                                                    <FormItem
                                                        key={item.value}
                                                        className="flex flex-row items-start space-x-3 space-y-0"
                                                    >
                                                        <FormControl>
                                                            <Checkbox
                                                                checked={checked}
                                                                disabled={item.disabled}
                                                                onCheckedChange={(checked) => {
                                                                    if (item.disabled) {
                                                                        return
                                                                    }

                                                                    return checked
                                                                        ? field.onChange([
                                                                              ...(field.value || []),
                                                                              item.value,
                                                                          ])
                                                                        : field.onChange(
                                                                              field.value?.filter(
                                                                                  (value: string) =>
                                                                                      value !== item.value
                                                                              )
                                                                          )
                                                                }}
                                                            />
                                                        </FormControl>
                                                        <FormLabel className="font-normal">{item.label}</FormLabel>
                                                    </FormItem>
                                                    <Separator className="my-2" />
                                                </>
                                            )
                                        }}
                                    />
                                ))
                            )}
                        </div>
                    </ScrollArea>
                    <FormDescription>{description}</FormDescription>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
