import { FormInput } from '@/components/form/elements/form-input'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormCheckboxGroup } from '@/components/form/elements/form-checkbox-group'
import { FormDatePicker } from '@/components/form/elements/form-date-picker'
import { StoreDeployTokenInput } from '@/api'
import { RepositoryPackageTree } from '@/components/form/elements/repository-package-tree'

export function DeployTokenFormElements({ form }: { form: UseFormReturn<StoreDeployTokenInput> }) {
    return (
        <div className="flex gap-6">
            <div className="space-y-4 min-w-[470px]">
                <FormInput
                    label="Token Name"
                    name="name"
                    description="Enter a name for this deploy token to easily identify its purpose."
                    control={form.control}
                />
                <FormDatePicker
                    label="Expiration Date"
                    name="expiresAt"
                    description="Optionally set an expiration date, after which it will no longer be valid."
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
            </div>
            <div className="space-y-4 grow">
                <RepositoryPackageTree
                    label="Repositories & Packages"
                    description="Select repositories for full access, or expand a repository and select only specific packages."
                    control={form.control}
                />
            </div>
        </div>
    )
}
