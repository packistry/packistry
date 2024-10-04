import * as React from 'react'

import { FormSelect, FormSelectProps } from '@/components/form/elements/FormSelect'
import { Optional } from '@tanstack/react-query'
import { useRepositories } from '@/api/hooks'

export function FormRepositorySelect(props: Omit<Optional<FormSelectProps, 'name' | 'label'>, 'options'>) {
    const query = useRepositories({
        // @todo add an all option?
        size: 1000,
    })

    return (
        <FormSelect
            label="Repository"
            name="repository"
            {...props}
            options={(query.data?.data || []).map((repository) => ({
                value: repository.id,
                label: repository.name ?? 'Root',
            }))}
        />
    )
}
