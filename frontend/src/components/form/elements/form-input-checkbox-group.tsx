import * as React from 'react'
import { useState } from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Checkbox } from '@/components/ui/checkbox'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { Input } from '@/components/ui/input'
import { FormElement } from '@/components/form/elements/element'

export type FormInputCheckboxGroupProps = {
    loading?: boolean
    options: string[]
    search?: string
    onSearch?: (search: string) => void
} & FormElement


export function FormInputCheckboxGroup({
                                            control,
                                            label,
                                            name,
                                            options,
                                            placeholder,
                                            loading = false,
                                            search,
                                            onSearch,
                                            description,
                                        }: FormInputCheckboxGroupProps) {
    const [searchTerm, setSearchTerm] = useState('')

    const filteredOptions = onSearch
        ? options
        : options.filter((option) => option.toLowerCase().includes(searchTerm.toLowerCase()))

    const dynamicFilteredOptions = (searchTerm && !filteredOptions.includes(searchTerm.toLowerCase()))
        ? [...filteredOptions, searchTerm.toLowerCase()]
        : filteredOptions

    return (
        <FormField
            control={control}
            name={name}
            render={() => (
                <FormItem>
                    <FormLabel className="text-base">{label}</FormLabel>
                    <Input
                        type="search"
                        placeholder={placeholder || 'Search or add...'}
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
                                dynamicFilteredOptions.map((item) => (
                                    <FormField
                                        key={item}
                                        control={control}
                                        name={name}
                                        render={({ field }) => {
                                            return (
                                                <>
                                                    <FormItem
                                                        key={item}
                                                        className="flex flex-row items-start space-x-3 space-y-0"
                                                    >
                                                        <FormControl>
                                                            <Checkbox
                                                                checked={field.value?.includes(item)}
                                                                onCheckedChange={(checked) => {
                                                                    return checked
                                                                        ? field.onChange([...field.value, item])
                                                                        : field.onChange(
                                                                            field.value?.filter(
                                                                                (value: string) =>
                                                                                    value !== item
                                                                            )
                                                                        )
                                                                }}
                                                            />
                                                        </FormControl>
                                                        <FormLabel className="font-normal">{item}</FormLabel>
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
