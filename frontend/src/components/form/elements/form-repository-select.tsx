import * as React from 'react'

import { FormSelect, FormSelectProps } from '@/components/form/elements/form-select'
import { Optional } from '@tanstack/react-query'
import { useRepositories } from '@/api/hooks'
import { CodeIcon } from 'lucide-react'
import { Link } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'

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
            empty={{
                title: 'No Repositories',
                icon: <CodeIcon />,
                description:
                    "You haven't created any repositories yet. Create a repository first to serve the package from.",
                button: (
                    <Link
                        to="/repositories"
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Add Repository
                        </Button>
                    </Link>
                ),
            }}
            options={(query.data?.data || []).map((repository) => ({
                value: repository.id,
                label: repository.name,
            }))}
        />
    )
}
