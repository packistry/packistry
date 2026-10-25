import * as React from 'react'
import { useRef } from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormInput } from '@/components/form/elements/form-input'
import { FormUpstreamFilters } from '@/components/form/elements/form-upstream-filters'
import { FormUpstreamCredentials } from '@/components/form/elements/form-upstream-credentials'
import { StoreUpstreamInput, UpdateUpstreamInput } from '@/api/upstream'

/**
 * Any Composer 2 repository: GitLab group registries, Private Packagist, Satis or another Packistry.
 */
export function ComposerUpstreamFormElements({
    form,
}: {
    form: UseFormReturn<StoreUpstreamInput | UpdateUpstreamInput>
}) {
    const previousHost = useRef(hostOf(form.getValues('url')))

    // credentials for the upstream's own host follow the url while it is being edited
    function onUrlChange(url: string) {
        const host = hostOf(url)

        if (host === null || previousHost.current === null || host === previousHost.current) {
            previousHost.current = host ?? previousHost.current
            return
        }

        const credentials = form.getValues('credentials') || []

        credentials.forEach((credential, index) => {
            if (credential.host === previousHost.current) {
                form.setValue(`credentials.${index}.host`, host)
            }
        })

        previousHost.current = host
    }

    return (
        <div className="flex gap-6">
            <div className="space-y-4 min-w-[470px] max-w-[470px]">
                <FormInput
                    label="Name"
                    name="name"
                    description="A name to identify this upstream."
                    control={form.control}
                />
                <FormInput
                    label="URL"
                    name="url"
                    onChange={(event) => onUrlChange(event.target.value)}
                    description={
                        <>
                            The URL of a Composer 2 repository or its packages.json, e.g.{' '}
                            <code className="break-all">
                                https://gitlab.com/api/v4/group/123/-/packages/composer/packages.json
                            </code>{' '}
                            for a GitLab group, <code className="break-all">https://repo.packagist.com/acme</code> for
                            Private Packagist, or the URL of a Satis or Packistry repository.
                        </>
                    }
                    control={form.control}
                />
                <FormUpstreamFilters control={form.control} />
            </div>
            <div className="space-y-4 grow">
                <FormUpstreamCredentials form={form} />
            </div>
        </div>
    )
}

function hostOf(url: string): string | null {
    try {
        return new URL(url).hostname.toLowerCase()
    } catch {
        return null
    }
}
