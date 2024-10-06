import { FormInput } from '@/components/form/elements/FormInput'
import * as React from 'react'
import { Control } from 'react-hook-form'
import { FormCheckboxGroup } from '@/components/form/elements/FormCheckboxGroup'
import { FormDatePicker } from '@/components/form/elements/FormDatePicker'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function PersonalTokenFormElements({ control }: { control: Control<any> }) {
    return (
        <>
            <FormInput
                label="Token Name"
                name="name"
                control={control}
            />
            <FormCheckboxGroup
                options={[
                    { value: 'repository:read', label: 'Read' },
                    { value: 'repository:write', label: 'Write' },
                ]}
                name="abilities"
                label="Access Rights"
                control={control}
            />
            <FormDatePicker
                label="Expiration Date"
                name="expiresAt"
                control={control}
            />
        </>
    )
}
