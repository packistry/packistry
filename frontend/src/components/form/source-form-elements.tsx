import { FormInput } from '@/components/form/elements/FormInput'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { defaultSourceUrlMap, FormSourceProviderSelect } from '@/components/form/elements/FormSourceProviderSelect'

export function SourceFormElements({ form }: { form: UseFormReturn }) {
    return (
        <>
            <FormInput
                name="name"
                label="Name"
                control={form.control}
            />
            <FormSourceProviderSelect
                onChange={(value) => {
                    if (value in defaultSourceUrlMap) {
                        form.setValue('url', defaultSourceUrlMap[value])
                    }
                }}
                control={form.control}
            />
            <FormInput
                label="URL"
                name="url"
                placeholder="e.g. https://sub.domain.com"
                control={form.control}
            />
            <FormInput
                label="Token"
                name="token"
                type="password"
                control={form.control}
            />
        </>
    )
}
