import * as React from 'react'
import { FormStringList } from '@/components/form/elements/form-string-list'
import { FormElement } from '@/components/form/elements/element'

export function FormUpstreamFilters({ control }: { control: FormElement['control'] }) {
    return (
        <>
            <FormStringList
                label="Only these packages"
                name="only"
                placeholder="acme/*"
                description="Only ask this upstream for packages matching one of these patterns. Strongly recommended for private upstreams, it keeps lookups fast and makes sure the names of private packages are never sent to other upstreams."
                control={control}
            />
            <FormStringList
                label="Exclude packages"
                name="exclude"
                placeholder="acme/*"
                description="Never ask this upstream for packages matching one of these patterns, e.g. exclude your private vendor from Packagist."
                control={control}
            />
        </>
    )
}
