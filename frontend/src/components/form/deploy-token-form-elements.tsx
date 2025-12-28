import { FormInput } from '@/components/form/elements/form-input'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormCheckboxGroup } from '@/components/form/elements/form-checkbox-group'
import { FormDatePicker } from '@/components/form/elements/form-date-picker'
import { FormRepositorySearchCheckboxGroup } from '@/components/form/elements/form-repository-search-checkbox-group'
import { FormPackageSearchCheckboxGroup } from '@/components/form/elements/form-package-search-checkbox-group'
import { StoreDeployTokenInput } from '@/api'

export function DeployTokenFormElements({ form }: { form: UseFormReturn<StoreDeployTokenInput> }) {
    return (
        <>
            <FormInput
                label="Token Name"
                name="name"
                description="Enter a name for this deploy token to easily identify its purpose."
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
            <FormRepositorySearchCheckboxGroup
                label="Repositories"
                description="Select the repositories it should have access to."
                control={form.control}
            />
            <FormPackageSearchCheckboxGroup
                label="Packages"
                description="Optionally select specific packages for granular access control. If no packages are selected, the token will have access to all packages in the selected repositories."
                control={form.control}
            />
        </>
    )
}
