import * as React from 'react'
import { Optional } from '@/helpers'
import {
    FormInputCheckboxGroup,
    FormInputCheckboxGroupProps,
} from '@/components/form/elements/form-input-checkbox-group'

export type FormAuthenticationProviderDomainCheckboxGroupProps = Omit<Optional<FormInputCheckboxGroupProps, 'name' | 'label'>, 'options'>
export function FormAuthenticationProviderDomainCheckboxGroup(props: FormAuthenticationProviderDomainCheckboxGroupProps) {
    const options = props.options || []

    return (
        <FormInputCheckboxGroup
            options={options}
            // loading={query.isLoading}
            name="allowedDomains"
            label="Allowed domains"
            {...props}
        />
    )
}
