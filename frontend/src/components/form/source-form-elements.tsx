import { FormInput } from '@/components/form/elements/FormInput'
import * as React from 'react'
import { ReactElement } from 'react'
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
                description="Provide a name for this source to easily identify it."
                control={form.control}
            />
            <FormSourceProviderSelect
                disabled={disableProvider}
                description="Select the platform where your source is hosted."
                onChange={(value) => {
                    form.setValue('url', providerUrls[value as SourceProvider])
                }}
                control={form.control}
            />
            <FormInput
                label="URL"
                name="url"
                placeholder="e.g. https://sub.domain.com"
                description="For self-hosted variants update the url e.g. https://git.company.com."
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
                description="Enter your access token for authentication."
                control={form.control}
            />

            {provider === 'bitbucket' && (
                <FormInput
                    label="Workspace"
                    name="metadata.workspace"
                    control={form.control}
                    description="Private repositories may be accessed within a workspace."
                />
            )}
        </>
    )
}

function TokenCreationAlert({ url, provider }: { url: string; provider: SourceProvider }) {
    const fullUrl = url.indexOf('://') === -1 ? 'https://' + url : url

    const providerExplanations: Record<SourceProvider, ReactElement> = {
        gitea: (
            <>
                Navigate to{' '}
                <a
                    rel="noreferrer"
                    target="_blank"
                    className="underline"
                    href={fullUrl + '/user/settings/applications'}
                >
                    {fullUrl}
                    /user/settings/applications
                </a>{' '}
                and create a token with repository read and write permission
            </>
        ),
        github: (
            <>
                Navigate to{' '}
                <a
                    rel="noreferrer"
                    target="_blank"
                    className="underline"
                    href={'https://github.com/settings/tokens/new'}
                >
                    https://github.com/settings/tokens/new
                </a>{' '}
                and create a token with repo scope
            </>
        ),
        gitlab: (
            <>
                Navigate to{' '}
                <a
                    rel="noreferrer"
                    target="_blank"
                    className="underline"
                    href={fullUrl + '/-/user_settings/personal_access_tokens'}
                >
                    {fullUrl + '/-/user_settings/personal_access_tokens'}
                </a>{' '}
                and create a token with api scope
            </>
        ),
        bitbucket: (
            <>
                Navigate to{' '}
                <a
                    rel="noreferrer"
                    target="_blank"
                    className="underline"
                    href={'https://bitbucket.org/account/settings/app-passwords/'}
                >
                    https://bitbucket.org/account/settings/app-passwords/
                </a>{' '}
                and create an app password with repository read/admin and webhook read and write permissions. Base64
                encode username:app-password to create a token
            </>
        ),
    }

    return (
        <Alert>
            <Info className="h-4 w-4" />
            <AlertTitle>{providerNames[provider]}</AlertTitle>
            <AlertDescription>{providerExplanations[provider]}</AlertDescription>
        </Alert>
    )
}
