import * as React from 'react'
import { Optional } from '@tanstack/react-query'
import { useRepositories } from '@/api/hooks'
import {
    FormSearchCheckboxGroup,
    FormSearchCheckboxGroupProps,
} from '@/components/form/elements/FormSearchCheckboxGroup'

export type FormRepositorySearchCheckboxGroupProps = {
    filters?: Parameters<typeof useRepositories>[0]['filters']
} & Omit<Optional<FormSearchCheckboxGroupProps, 'name' | 'label'>, 'options'>
export function FormRepositorySearchCheckboxGroup(props: FormRepositorySearchCheckboxGroupProps) {
    const query = useRepositories({
        // @todo add an all option?
        size: 1000,
        filters: props.filters,
    })

    const options = (query.data?.data || [])
        .map((repository) => ({
            value: repository.id,
            label: repository.name,
        }))
        .sort((a, b) => a.label.localeCompare(b.label))

    return (
        <FormSearchCheckboxGroup
            options={options}
            loading={query.isLoading}
            name="repositories"
            label="Repositories"
            {...props}
        />
    )
}
