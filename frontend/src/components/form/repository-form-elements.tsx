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
                description="The name will also be the directory this repository will be served from"
                label="Name"
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
