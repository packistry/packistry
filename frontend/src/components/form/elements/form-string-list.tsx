import * as React from 'react'
import { useState } from 'react'
import { Plus, X } from 'lucide-react'
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { FormElement } from '@/components/form/elements/element'

export type FormStringListProps = {
    normalize?: (value: string) => string
} & FormElement

export function FormStringList({
    control,
    label,
    name,
    placeholder,
    description,
    normalize = (value) => value.trim(),
}: FormStringListProps) {
    const [newValue, setNewValue] = useState('')

    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => {
                const values: string[] = field.value || []

                function add() {
                    const value = normalize(newValue)

                    if (value && !values.includes(value)) {
                        field.onChange([...values, value])
                    }

                    setNewValue('')
                }

                return (
                    <FormItem>
                        <FormLabel>{label}</FormLabel>
                        <FormControl>
                            <div className="space-y-2">
                                <div className="flex gap-2">
                                    <Input
                                        placeholder={placeholder}
                                        value={newValue}
                                        onChange={(event) => setNewValue(event.target.value)}
                                        onKeyDown={(event) => {
                                            if (event.key === 'Enter') {
                                                event.preventDefault()
                                                add()
                                            }
                                        }}
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        onClick={add}
                                        disabled={!newValue.trim()}
                                    >
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                                {values.length > 0 && (
                                    <div className="flex flex-wrap gap-2">
                                        {values.map((value) => (
                                            <span
                                                key={value}
                                                className="inline-flex items-center gap-1 rounded-md border bg-muted/30 pl-2 text-sm font-mono"
                                            >
                                                {value}
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-7 px-2"
                                                    onClick={() =>
                                                        field.onChange(values.filter((item) => item !== value))
                                                    }
                                                >
                                                    <X className="h-3 w-3" />
                                                </Button>
                                            </span>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </FormControl>
                        {description && <FormDescription>{description}</FormDescription>}
                        <FormMessage />
                    </FormItem>
                )
            }}
        />
    )
}
