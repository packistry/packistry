import * as React from 'react'
import { useState } from 'react'
import { useDebounce } from 'use-debounce'

import { FormSelectProps } from '@/components/form/elements/FormSelect'
import { Optional } from '@tanstack/react-query'
import { useSourceProjects } from '@/api/hooks'
import { FormSearchCheckboxGroup } from '@/components/form/elements/FormSearchCheckboxGroup'

export function FormSourceProjectCheckboxGroup(
    props: Omit<Optional<FormSelectProps, 'name' | 'label'>, 'options'> & {
        source?: string
    }
) {
    const [searchTerm, setSearchTerm] = useState('')
    const [debouncedSearchTerm] = useDebounce(searchTerm, 300)

    const query = useSourceProjects(props.source, debouncedSearchTerm)

    return (
        <FormSearchCheckboxGroup
            label="Projects"
            name="projects"
            {...props}
            search={searchTerm}
            onSearch={(search) => {
                setSearchTerm(search)
            }}
            placeholder="Starts searching after 3 characters"
            loading={query.isPending}
            options={(query.data || [])
                .map((repository) => ({
                    value: repository.id,
                    label: repository.fullName,
                }))
                .sort((a, b) => a.label.localeCompare(b.label))}
        />
    )
}
