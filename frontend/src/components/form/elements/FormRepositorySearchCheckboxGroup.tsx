import * as React from 'react'
import { Optional } from '@tanstack/react-query'
import { useRepositories } from '@/api/hooks'
import {
    FormSearchCheckboxGroup,
    FormSearchCheckboxGroupProps,
} from '@/components/form/elements/FormSearchCheckboxGroup'

export function FormRepositorySearchCheckboxGroup(
    props: Omit<Optional<FormSearchCheckboxGroupProps, 'name' | 'label'>, 'options'>
) {
    const query = useRepositories({
        // @todo add an all option?
        size: 1000,
        filters: {
            public: false,
        },
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
