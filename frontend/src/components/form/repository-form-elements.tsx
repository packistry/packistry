import { FormInput } from '@/components/form/elements/FormInput'
import { FormSwitch } from '@/components/form/elements/FormSwitch'
import * as React from 'react'
import { Control } from 'react-hook-form'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function RepositoryFormElements({ control }: { control: Control<any> }) {
    return (
        <>
            <FormInput
                name="name"
                label="Name"
                description="Provide a descriptive name for this repository. This will help identify it among others."
                control={control}
            />
            <FormInput
                name="path"
                description={`The base path where your repository will be served (e.g., 'plugins' will be accessible at ${window.location.host}/plugins). Leave blank to serve from the root (/) path.`}
                label="Path"
                control={control}
            />
            <FormInput
                name="description"
                label="Description"
                description="Provide a brief summary of this repository's purpose."
                control={control}
            />
            <FormSwitch
                label="Public"
                description="If the repository is public, any one can download its packages without needing authentication."
                name="public"
                control={control}
            />
        </>
    )
}
