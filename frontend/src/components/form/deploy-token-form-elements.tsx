import { FormInput } from '@/components/form/elements/FormInput'
import * as React from 'react'
import { Control } from 'react-hook-form'
import { FormCheckboxGroup } from '@/components/form/elements/FormCheckboxGroup'
import { FormDatePicker } from '@/components/form/elements/FormDatePicker'
import { FormRepositorySearchCheckboxGroup } from '@/components/form/elements/FormRepositorySearchCheckboxGroup'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function DeployTokenFormElements({ control }: { control: Control<any> }) {
    return (
        <>
            <FormInput
                label="Token Name"
                name="name"
                description="Enter a name for this deploy token to easily identify its purpose."
                control={control}
            />
            <FormCheckboxGroup
                options={[
                    { value: 'repository:read', label: 'Read' },
                    { value: 'repository:write', label: 'Write' },
                ]}
                name="abilities"
                label="Access Rights"
                description="Give write access, if you want to upload package zips to a repository"
                control={control}
            />
            <FormDatePicker
                label="Expiration Date"
                name="expiresAt"
                description="Optionally set an expiration date, after which it will no longer be valid."
                control={control}
            />
            <FormRepositorySearchCheckboxGroup
                label="Private Repositories"
                description="Select the private repositories it should have access to."
                control={control}
            />
        </>
    )
}
