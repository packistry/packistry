import * as React from 'react'
import { useFieldArray, UseFormReturn } from 'react-hook-form'
import { Plus, Trash2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { FormInput } from '@/components/form/elements/form-input'
import { FormSelect } from '@/components/form/elements/form-select'
import {
    StoreUpstreamInput,
    UpdateUpstreamInput,
    upstreamCredentialTypeNames,
    upstreamCredentialTypes,
} from '@/api/upstream'

export function FormUpstreamCredentials({ form }: { form: UseFormReturn<StoreUpstreamInput | UpdateUpstreamInput> }) {
    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: 'credentials',
        // credentials carry their own database id
        keyName: 'key',
    })

    const credentials = form.watch('credentials') || []

    return (
        <div className="space-y-3">
            <div>
                <p className="text-sm font-medium">Credentials</p>
                <p className="text-sm text-muted-foreground">
                    Credentials are only sent to their host. An upstream with credentials is private, private upstreams
                    cannot be used by public repositories.
                </p>
            </div>
            {fields.map((field, index) => {
                const credential = credentials[index]
                const type = credential?.type
                const existing = typeof credential?.id !== 'undefined' && credential.id !== ''

                return (
                    <div
                        key={field.key}
                        className="rounded-md border p-3 space-y-3"
                    >
                        <div className="flex gap-3 items-start">
                            <div className="grow">
                                <FormInput
                                    label="Host"
                                    name={`credentials.${index}.host`}
                                    placeholder="gitlab.com"
                                    control={form.control}
                                />
                            </div>
                            <div className="w-44">
                                <FormSelect
                                    label="Type"
                                    name={`credentials.${index}.type`}
                                    control={form.control}
                                    options={upstreamCredentialTypes.map((value) => ({
                                        value,
                                        label: upstreamCredentialTypeNames[value],
                                    }))}
                                />
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="mt-6"
                                onClick={() => remove(index)}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        </div>
                        <div className="flex gap-3 items-start">
                            {type !== 'bearer' && (
                                <div className="w-1/2">
                                    <FormInput
                                        label={type === 'header' ? 'Header name' : 'Username'}
                                        name={`credentials.${index}.username`}
                                        placeholder={type === 'header' ? 'PRIVATE-TOKEN' : ''}
                                        control={form.control}
                                    />
                                </div>
                            )}
                            <div className="grow">
                                <FormInput
                                    label={type === 'http-basic' ? 'Password / token' : 'Token'}
                                    name={`credentials.${index}.secret`}
                                    type="password"
                                    placeholder={existing ? 'Leave empty to keep the current secret' : ''}
                                    control={form.control}
                                />
                            </div>
                        </div>
                    </div>
                )
            })}
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => append({ host: '', type: 'http-basic', username: '', secret: '' })}
            >
                <Plus className="h-4 w-4 mr-2" />
                Add credential
            </Button>
            {/* errors that belong to the credentials as a whole */}
            {typeof form.formState.errors.credentials?.message === 'string' && (
                <p className="text-sm font-medium text-destructive">{form.formState.errors.credentials.message}</p>
            )}
        </div>
    )
}
