import * as React from 'react'

import { Optional } from '@tanstack/react-query'
import { FormSelect, FormSelectProps } from '@/components/form/elements/form-select'
import { providerNames, sourceProviders } from '@/api/source-provider'

export function FormSourceProviderSelect(props: Omit<Optional<FormSelectProps, 'name' | 'label'>, 'options'>) {
    return (
        <FormSelect
            label="Provider"
            name="provider"
            {...props}
            options={sourceProviders.map((provider) => ({
                value: provider,
                label: providerNames[provider],
            }))}
        />
    )
}
