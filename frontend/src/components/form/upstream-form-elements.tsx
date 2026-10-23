import * as React from 'react'
import { useRef, useState } from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormInput } from '@/components/form/elements/form-input'
import { FormStringList } from '@/components/form/elements/form-string-list'
import { FormUpstreamCredentials } from '@/components/form/elements/form-upstream-credentials'
import { StoreUpstreamInput, UpdateUpstreamInput, upstreamPresets } from '@/api/upstream'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

export function UpstreamFormElements({
    form,
    withPresets = false,
}: {
    form: UseFormReturn<StoreUpstreamInput | UpdateUpstreamInput>
    withPresets?: boolean
}) {
    const [presetKey, setPresetKey] = useState<string>()
    const preset = upstreamPresets.find(({ key }) => key === presetKey)
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

    function applyPreset(key: string) {
        const preset = upstreamPresets.find((preset) => preset.key === key)

        setPresetKey(key)

        if (!preset) {
            return
        }

        form.setValue('name', preset.name)
        form.setValue('url', preset.url)
        previousHost.current = hostOf(preset.url)
        form.setValue('credentials', preset.credential ? [{ ...preset.credential, secret: '' }] : [])
    }

    return (
        <div className="flex gap-6">
            <div className="space-y-4 min-w-[470px] max-w-[470px]">
                {withPresets && (
                    <div className="space-y-2">
                        <Label>Type</Label>
                        <Select
                            value={presetKey}
                            onValueChange={applyPreset}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select the kind of registry" />
                            </SelectTrigger>
                            <SelectContent>
                                {upstreamPresets.map((preset) => (
                                    <SelectItem
                                        key={preset.key}
                                        value={preset.key}
                                    >
                                        {preset.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {preset && <p className="text-sm text-muted-foreground">{preset.description}</p>}
                    </div>
                )}
                <FormInput
                    label="Name"
                    name="name"
                    description="A name to identify this upstream, e.g. the GitLab group it mirrors."
                    control={form.control}
                />
                <FormInput
                    label="URL"
                    name="url"
                    onChange={(event) => onUrlChange(event.target.value)}
                    description="The URL of the Composer repository or its packages.json. It must provide a Composer 2 metadata-url."
                    control={form.control}
                />
                <FormStringList
                    label="Only these packages"
                    name="only"
                    placeholder="acme/*"
                    description="Only ask this upstream for packages matching one of these patterns. Strongly recommended for private upstreams, it keeps lookups fast and makes sure the names of private packages are never sent to other upstreams."
                    control={form.control}
                />
                <FormStringList
                    label="Exclude packages"
                    name="exclude"
                    placeholder="acme/*"
                    description="Never ask this upstream for packages matching one of these patterns, e.g. exclude your private vendor from Packagist."
                    control={form.control}
                />
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
