import { FormInput } from '@/components/form/elements/form-input'
import { FormSwitch } from '@/components/form/elements/form-switch'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { StoreRepositoryInput, UpdateRepositoryInput } from '@/api'

export function RepositoryFormElements({
    form,
}: {
    form: UseFormReturn<StoreRepositoryInput | UpdateRepositoryInput>
}) {
    return (
        <>
            <FormInput
                name="name"
                label="Name"
                description="Provide a descriptive name for this repository. This will help identify it among others."
                control={form.control}
            />
            <FormInput
                name="path"
                description={`The base path where your repository will be served (e.g., 'plugins' will be accessible at ${window.location.host}/r/plugins). Leave blank to serve from the root (${window.location.host}/) path.`}
                label="Path"
                control={form.control}
            />
            <FormInput
                name="description"
                label="Description"
                description="Provide a brief summary of this repository's purpose."
                control={form.control}
            />
            <FormSwitch
                label="Public"
                description="If the repository is public, any one can download its packages without needing authentication."
                name="public"
                control={form.control}
            />
        </>
    )
}
