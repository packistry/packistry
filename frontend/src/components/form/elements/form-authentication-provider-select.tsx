import * as React from 'react'

import { Optional } from '@tanstack/react-query'
import { FormSelect, FormSelectProps } from '@/components/form/elements/form-select'
import { authenticationProviders, providerNames } from '@/api/authentication-provider'

export function FormAuthenticationProviderSelect(props: Omit<Optional<FormSelectProps, 'name' | 'label'>, 'options'>) {
    return (
        <FormSelect
            label="Provider"
            name="provider"
            {...props}
            options={authenticationProviders.map((provider) => ({
                value: provider,
                label: providerNames[provider],
            }))}
        />
    )
}
