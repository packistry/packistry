import * as React from 'react'

import { FormSelect, FormSelectProps } from '@/components/form/elements/form-select'
import { authenticationProviders, providerNames } from '@/api/authentication-provider'
import { Optional } from '@/helpers'

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
