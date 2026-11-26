import * as React from 'react'

import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { cn } from '@/lib/utils'
import { addDays, format } from 'date-fns'
import { CalendarIcon } from 'lucide-react'
import { Calendar } from '@/components/ui/calendar'
import { Button } from '@/components/ui/button'
import { FormElement } from '@/components/form/elements/element'
import { ControllerRenderProps, FieldValues } from 'react-hook-form'

export type FormDatePickerProps = FormElement

const presets = [
    { value: 'never', label: 'No expiration' },
    { value: '7', label: '7 days' },
    { value: '30', label: '30 days' },
    { value: '60', label: '60 days' },
    { value: '90', label: '90 days' },
    { value: '180', label: '180 days' },
    { value: 'custom', label: 'Custom' },
]

function DatePickerField({
    field,
    placeholder,
}: {
    field: ControllerRenderProps<FieldValues, string>
    placeholder: string
}) {
    // dialog content remounts on reopen while the form keeps its value
    const [preset, setPreset] = React.useState(() => (field.value ? 'custom' : 'never'))

    function onPresetChange(value: string) {
        setPreset(value)
        if (value === 'never') {
            field.onChange('')
        } else if (value !== 'custom') {
            field.onChange(addDays(new Date(), Number(value)))
        }
    }

    return (
        <div className="flex gap-2">
            <Select
                value={preset}
                onValueChange={onPresetChange}
            >
                <FormControl>
                    <SelectTrigger className="w-[160px] shrink-0">
                        <SelectValue />
                    </SelectTrigger>
                </FormControl>
                <SelectContent>
                    {presets.map(({ value, label }) => (
                        <SelectItem
                            key={value}
                            value={value}
                        >
                            {label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Popover>
                <PopoverTrigger asChild>
                    <Button
                        variant={'outline'}
                        aria-label="Expiration date"
                        disabled={preset !== 'custom'}
                        className={cn(
                            'flex-1 justify-start text-left font-normal',
                            !field.value && 'text-muted-foreground'
                        )}
                    >
                        {field.value ? format(field.value, 'PPP') : <span>{placeholder}</span>}
                        <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    className="w-auto p-0"
                    align="start"
                >
                    <Calendar
                        mode="single"
                        selected={field.value}
                        onSelect={field.onChange}
                        disabled={(date) => date < new Date()}
                    />
                </PopoverContent>
            </Popover>
        </div>
    )
}

export function FormDatePicker({
    control,
    label,
    name,
    placeholder = 'Pick a date',
    description,
}: FormDatePickerProps) {
    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <DatePickerField
                        field={field}
                        placeholder={placeholder}
                    />
                    {description && <FormDescription>{description}</FormDescription>}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
