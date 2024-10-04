import { FormInput } from '@/components/form/elements/FormInput'
import * as React from 'react'
import { Control } from 'react-hook-form'
import { FormSourceProviderSelect } from '@/components/form/elements/FormSourceProviderSelect'

export function SourceFormElements({ control }: { control: Control<any> }) {
    return (
        <>
            <FormInput
                name="name"
                label="Name"
                control={control}
            />
            <FormSourceProviderSelect control={control} />
            <FormInput
                label="URL"
                name="url"
                placeholder="e.g. https://sub.domain.com"
                control={control}
            />
            <FormInput
                label="Token"
                name="token"
                type="password"
                control={control}
            />
        </>
    )
}
