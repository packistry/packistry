import * as React from 'react'
import { Optional } from '@/helpers'
import {
    FormInputCheckboxGroup,
    FormInputCheckboxGroupProps,
} from '@/components/form/elements/form-input-checkbox-group'

export type FormAuthenticationProviderDomainCheckboxGroupProps = Optional<FormInputCheckboxGroupProps, 'name' | 'label'>
export function FormAuthenticationProviderDomainCheckboxGroup(props: FormAuthenticationProviderDomainCheckboxGroupProps) {
    const options = props.options || []

    return (
        <FormInputCheckboxGroup
            options={options}
            name="allowedDomains"
            label="Allowed domains"
            {...props}
        />
    )
}
