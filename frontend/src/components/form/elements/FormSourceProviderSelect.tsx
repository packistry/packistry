import * as React from 'react'

import { Optional } from '@tanstack/react-query'
import { FormSelect, FormSelectProps } from '@/components/form/elements/FormSelect'

export const sourceProviders = ['Gitea', 'Github', 'Gitlab']

export const defaultSourceUrlMap: Record<string, string> = {
    gitea: '',
    github: 'https://api.github.com',
    gitlab: 'https://gitlab.com',
}
export function FormSourceProviderSelect(props: Omit<Optional<FormSelectProps, 'name' | 'label'>, 'options'>) {
    return (
        <FormSelect
            label="Provider"
            name="provider"
            {...props}
            options={sourceProviders.map((provider) => ({
                value: provider.toLowerCase(),
                label: provider,
            }))}
        />
    )
}
