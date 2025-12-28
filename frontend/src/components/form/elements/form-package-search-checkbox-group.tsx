import * as React from 'react'
import { Optional } from '@/helpers'
import { usePackages } from '@/api/hooks'
import {
    FormSearchCheckboxGroup,
    FormSearchCheckboxGroupProps,
} from '@/components/form/elements/form-search-checkbox-group'

export type FormPackageSearchCheckboxGroupProps = {
    filters?: Parameters<typeof usePackages>[0]['filters']
} & Omit<Optional<FormSearchCheckboxGroupProps, 'name' | 'label'>, 'options'>
export function FormPackageSearchCheckboxGroup(props: FormPackageSearchCheckboxGroupProps) {
    const query = usePackages({
        // @todo add an all option?
        size: 1000,
        filters: props.filters,
    })

    const options = (query.data?.data || [])
        .map((pkg) => ({
            value: pkg.id,
            label: `${pkg.name}${pkg.repository ? ` (${pkg.repository.name})` : ''}`,
            description: pkg.description || undefined,
        }))
        .sort((a, b) => a.label.localeCompare(b.label))

    return (
        <FormSearchCheckboxGroup
            options={options}
            loading={query.isLoading}
            name="packages"
            label="Packages"
            {...props}
        />
    )
}
