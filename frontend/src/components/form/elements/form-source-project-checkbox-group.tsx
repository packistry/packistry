import * as React from 'react'
import { useState } from 'react'
import { useDebounce } from 'use-debounce'

import { FormSelectProps } from '@/components/form/elements/form-select'
import { Optional } from '@/helpers'
import { useSourceProjects } from '@/api/hooks'
import { FormSearchCheckboxGroup } from '@/components/form/elements/form-search-checkbox-group'
import { CircleX } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { isValidationError } from '@/api'

export function FormSourceProjectCheckboxGroup(
    props: Omit<Optional<FormSelectProps, 'name' | 'label'>, 'options'> & {
        source?: string
    }
) {
    const [searchTerm, setSearchTerm] = useState('')
    const [debouncedSearchTerm] = useDebounce(searchTerm, 300)

    const query = useSourceProjects(props.source, debouncedSearchTerm)

    return (
        <>
            {isValidationError(query.error) && (
                <Alert variant="destructive">
                    <CircleX className="h-4 w-4" />
                    <AlertTitle>Unable to Fetch Source Repositories</AlertTitle>
                    <AlertDescription>{query.error.response.data.message}</AlertDescription>
                </Alert>
            )}
            <FormSearchCheckboxGroup
                label="Projects"
                name="projects"
                {...props}
                search={searchTerm}
                onSearch={(search) => {
                    setSearchTerm(search)
                }}
                placeholder="Search begins after entering at least 3 characters."
                loading={query.isLoading}
                options={(query.data || [])
                    .map((repository) => ({
                        value: repository.id,
                        label: repository.fullName,
                    }))
                    .sort((a, b) => a.label.localeCompare(b.label))}
            />
        </>
    )
}
