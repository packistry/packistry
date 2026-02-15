import * as React from 'react'
import { useState } from 'react'
import { Plus, X } from 'lucide-react'
import { FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { FormElement } from '@/components/form/elements/element'

export type FormAuthenticationProviderDomainListProps = FormElement

export function FormAuthenticationProviderDomainList({
    control,
    label = 'Allowed domains',
    name = 'allowedDomains',
    description,
}: FormAuthenticationProviderDomainListProps) {
    const [newDomain, setNewDomain] = useState('')

    return (
        <FormField
            control={control}
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel className="text-base">{label}</FormLabel>
                    <FormControl>
                        <div className="space-y-3">
                            <div className="flex gap-2">
                                <Input
                                    placeholder="Enter domain (e.g. example.com)"
                                    value={newDomain}
                                    onChange={(e) => setNewDomain(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault()
                                            const domain = newDomain.trim().toLowerCase()
                                            if (domain && !field.value?.includes(domain)) {
                                                field.onChange([...(field.value || []), domain])
                                                setNewDomain('')
                                            }
                                        }
                                    }}
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={() => {
                                        const domain = newDomain.trim().toLowerCase()
                                        if (domain && !field.value?.includes(domain)) {
                                            field.onChange([...(field.value || []), domain])
                                            setNewDomain('')
                                        }
                                    }}
                                    disabled={!newDomain.trim()}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </div>

                            {field.value && field.value.length > 0 && (
                                <div className="space-y-2 max-h-64 overflow-y-auto">
                                    {field.value.map((domain: string, index: number) => (
                                        <div
                                            key={index}
                                            className="flex items-center justify-between p-2 border rounded-md bg-muted/30"
                                        >
                                            <span className="text-sm">{domain}</span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    const newDomains = field.value.filter(
                                                        (_: string, i: number) => i !== index
                                                    )
                                                    field.onChange(newDomains)
                                                }}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </FormControl>
                    <FormDescription>{description}</FormDescription>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}
