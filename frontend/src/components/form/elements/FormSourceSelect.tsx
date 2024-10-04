import * as React from 'react'

import { FormSelect, FormSelectProps } from '@/components/form/elements/FormSelect'
import { Optional } from '@tanstack/react-query'
import { useSources } from '@/api/hooks'
import { CodeIcon } from 'lucide-react'
import { Link } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'

export function FormSourceSelect(props: Omit<Optional<FormSelectProps, 'name' | 'label'>, 'options'>) {
    const query = useSources()

    return (
        <FormSelect
            label="Source"
            name="source"
            {...props}
            empty={{
                title: 'No Sources',
                icon: <CodeIcon />,
                description: "You haven't created any sources yet. Create a source to first to import from.",
                button: (
                    <Link
                        to="/sources"
                        search={{ open: true }}
                    >
                        <Button>
                            <span className="mr-2">+</span> Add Source
                        </Button>
                    </Link>
                ),
            }}
            options={(query.data || []).map((repository) => ({
                value: repository.id,
                label: repository.name,
            }))}
        />
    )
}
