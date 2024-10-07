import { FormInput } from '@/components/form/elements/FormInput'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormSourceProviderSelect } from '@/components/form/elements/FormSourceProviderSelect'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Info } from 'lucide-react'
import { providerNames, providerUrls, SourceProvider } from '@/api/source-provider'

export function SourceFormElements({ form, disableProvider }: { form: UseFormReturn; disableProvider?: boolean }) {
    const url = form.watch('url')
    const provider = form.watch('provider') as SourceProvider | undefined | ''

    return (
        <>
            <FormInput
                name="name"
                label="Name"
                control={form.control}
            />
            <FormSourceProviderSelect
                disabled={disableProvider}
                onChange={(value) => {
                    form.setValue('url', providerUrls[value as SourceProvider])
                }}
                control={form.control}
            />
            <FormInput
                label="URL"
                name="url"
                placeholder="e.g. https://sub.domain.com"
                description="For self-hosted variants update the url e.g. https://git.company.com"
                control={form.control}
            />
            {provider && (
                <TokenCreationAlert
                    url={url}
                    provider={provider}
                />
            )}
            <FormInput
                label="Token"
                name="token"
                type="password"
                control={form.control}
            />
        </>
    )
}
function isUrlValid(url: string) {
    try {
        new URL(url)
        return true
    } catch {
        return false
    }
}
function TokenCreationAlert({ url, provider }: { url: string; provider: SourceProvider }) {
    let fullUrl = url.indexOf('://') === -1 ? 'https://' + url : url

    // @todo meh
    if (fullUrl === 'https://api.github.com') {
        fullUrl = 'https://github.com'
    }

    const providerExplanations: Record<SourceProvider, { path: string; scopes: string }> = {
        gitea: {
            path: '/user/settings/applications',
            scopes: 'repository read and write permission',
        },
        github: {
            path: '/settings/tokens/new',
            scopes: 'repo scope',
        },
        gitlab: {
            path: '/-/user_settings/personal_access_tokens',
            scopes: 'api scope',
        },
    }

    const explanation = providerExplanations[provider]

    return (
        <Alert>
            <Info className="h-4 w-4" />
            <AlertTitle>{providerNames[provider]}</AlertTitle>
            <AlertDescription>
                Navigate to{' '}
                {isUrlValid(fullUrl) ? (
                    <a
                        rel="noreferrer"
                        href={fullUrl + explanation.path}
                    >
                        {fullUrl}
                        {explanation.path}
                    </a>
                ) : (
                    explanation.path
                )}{' '}
                and create a token with {explanation.scopes}.
            </AlertDescription>
        </Alert>
    )
}
