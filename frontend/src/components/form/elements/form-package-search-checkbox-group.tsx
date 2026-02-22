import * as React from 'react'
import { Optional } from '@/helpers'
import { usePackages } from '@/api/hooks'
import {
    FormSearchCheckboxGroup,
    FormSearchCheckboxGroupProps,
} from '@/components/form/elements/form-search-checkbox-group'

export type FormPackageSearchCheckboxGroupProps = {
    filters?: Parameters<typeof usePackages>[0]['filters']
    lockedRepositoryIds?: string[]
} & Omit<Optional<FormSearchCheckboxGroupProps, 'name' | 'label'>, 'options'>
export function FormPackageSearchCheckboxGroup(props: FormPackageSearchCheckboxGroupProps) {
    const query = usePackages({
        // @todo add an all option?
        size: 1000,
        include: ['repository'],
        filters: props.filters,
    })

    const lockedRepositoryIds = props.lockedRepositoryIds || []
    const lockedRepositoryIdsSet = new Set(lockedRepositoryIds.map((id) => Number(id)))

    const options = (query.data?.data || [])
        .map((pkg) => {
            const locked = lockedRepositoryIdsSet.has(pkg.repositoryId)

            return {
                value: pkg.id,
                label: `${pkg.repository?.name} -> ${pkg.name}`,
                checked: locked ? true : undefined,
                disabled: locked,
                description: pkg.description || undefined,
            }
        })
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
