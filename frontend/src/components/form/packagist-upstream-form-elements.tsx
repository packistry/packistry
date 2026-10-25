import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormInput } from '@/components/form/elements/form-input'
import { FormUpstreamFilters } from '@/components/form/elements/form-upstream-filters'
import { FormGithubToken } from '@/components/form/elements/form-github-token'
import { StoreUpstreamInput, UpdateUpstreamInput } from '@/api/upstream'

/**
 * Packagist has a fixed url, its only credential is an optional GitHub token for downloading dists.
 */
export function PackagistUpstreamFormElements({
    form,
}: {
    form: UseFormReturn<StoreUpstreamInput | UpdateUpstreamInput>
}) {
    return (
        <div className="flex gap-6">
            <div className="space-y-4 min-w-[470px] max-w-[470px]">
                <FormInput
                    label="Name"
                    name="name"
                    description="A name to identify this upstream."
                    control={form.control}
                />
                <FormUpstreamFilters control={form.control} />
            </div>
            <div className="space-y-4 grow">
                <FormGithubToken form={form} />
            </div>
        </div>
    )
}
