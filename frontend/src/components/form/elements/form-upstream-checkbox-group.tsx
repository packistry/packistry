import * as React from 'react'
import { FormSearchCheckboxGroup } from '@/components/form/elements/form-search-checkbox-group'
import { FormElement } from '@/components/form/elements/element'
import { useUpstreams } from '@/api/hooks'
import { useAuth } from '@/auth'
import { UPSTREAM_READ } from '@/permission'
import { Optional } from '@/helpers'

export function FormUpstreamCheckboxGroup(props: Optional<FormElement, 'name' | 'label'>) {
    const { can } = useAuth()
    const allowed = can(UPSTREAM_READ)
    const query = useUpstreams({ size: 1000, sort: 'name' }, { enabled: allowed })

    if (!allowed || (query.isSuccess && query.data.data.length === 0)) {
        return null
    }

    return (
        <FormSearchCheckboxGroup
            label="Upstreams"
            name="upstreams"
            placeholder="Search upstreams..."
            description="Access to every package mirrored from these upstreams, in all repositories that use them."
            loading={query.isLoading}
            options={(query.data?.data || []).map((upstream) => ({
                value: upstream.id,
                label: upstream.name,
            }))}
            {...props}
        />
    )
}
