import { FormInput } from '@/components/form/elements/form-input'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormCheckboxGroup } from '@/components/form/elements/form-checkbox-group'
import { FormDatePicker } from '@/components/form/elements/form-date-picker'
import { StorePersonalTokenInput } from '@/api'

export function PersonalTokenFormElements({ form }: { form: UseFormReturn<StorePersonalTokenInput> }) {
    return (
        <>
            <FormInput
                label="Token Name"
                name="name"
                description="Enter a name for this token to easily identify its purpose."
                control={form.control}
            />
            <FormCheckboxGroup
                options={[
                    { value: 'repository:read', label: 'Read' },
                    { value: 'repository:write', label: 'Write' },
                ]}
                name="abilities"
                label="Access Rights"
                description="Give write access, if you want to upload package zips to a repository"
                control={form.control}
            />
            <FormDatePicker
                label="Expiration Date"
                name="expiresAt"
                description="Optionally set an expiration date, after which it will no longer be valid."
                control={form.control}
            />
        </>
    )
}
