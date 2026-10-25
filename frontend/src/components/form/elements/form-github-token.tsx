import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { GITHUB_API_HOST, StoreUpstreamInput, UpdateUpstreamInput } from '@/api/upstream'

/**
 * The GitHub token of a Packagist upstream, stored as its bearer credential for the GitHub API.
 * An empty field keeps a stored token, removing it drops the credential.
 */
export function FormGithubToken({ form }: { form: UseFormReturn<StoreUpstreamInput | UpdateUpstreamInput> }) {
    const credentials = form.watch('credentials') || []
    const index = credentials.findIndex(({ host }) => host === GITHUB_API_HOST)
    const credential = index === -1 ? undefined : credentials[index]

    // the saved token, from the values the form was reset with
    const stored = (form.formState.defaultValues?.credentials || []).find(
        (saved) => saved?.host === GITHUB_API_HOST && typeof saved.id !== 'undefined'
    )
    const removing = typeof stored !== 'undefined' && typeof credential === 'undefined'
    const error = index === -1 ? undefined : form.formState.errors.credentials?.[index]?.secret?.message

    function setToken(token: string) {
        if (typeof credential === 'undefined') {
            if (token !== '') {
                form.setValue('credentials', [
                    ...credentials,
                    { host: GITHUB_API_HOST, type: 'bearer', username: null, secret: token },
                ])
            }

            return
        }

        if (token === '' && typeof credential.id === 'undefined') {
            removeCredential()

            return
        }

        form.setValue(`credentials.${index}.secret`, token)
    }

    function removeCredential() {
        form.setValue(
            'credentials',
            credentials.filter(({ host }) => host !== GITHUB_API_HOST)
        )
    }

    function keepStoredToken() {
        if (typeof stored === 'undefined') {
            return
        }

        form.setValue('credentials', [
            ...credentials,
            { id: stored.id, host: GITHUB_API_HOST, type: 'bearer', username: null, secret: '' },
        ])
    }

    return (
        <div className="space-y-2">
            <Label>GitHub token</Label>
            <div className="flex gap-2">
                <Input
                    type="password"
                    autoComplete="off"
                    disabled={removing}
                    placeholder={tokenPlaceholder(removing, typeof stored !== 'undefined')}
                    value={credential?.secret ?? ''}
                    onChange={(event) => setToken(event.target.value)}
                />
                {typeof stored !== 'undefined' && (
                    <Button
                        type="button"
                        variant="outline"
                        onClick={removing ? keepStoredToken : removeCredential}
                    >
                        {removing ? 'Keep token' : 'Remove token'}
                    </Button>
                )}
            </div>
            <p className="text-sm text-muted-foreground">
                Packagist dists are mostly downloaded from GitHub, which allows only 60 anonymous downloads per hour. A
                personal access token without any scopes raises that limit. It is only sent to {GITHUB_API_HOST}, and
                makes the upstream private: public repositories cannot use it.
            </p>
            {typeof error === 'string' && <p className="text-sm font-medium text-destructive">{error}</p>}
        </div>
    )
}

function tokenPlaceholder(removing: boolean, hasStoredToken: boolean): string {
    if (removing) {
        return 'The token is removed when you save'
    }

    if (hasStoredToken) {
        return 'Leave empty to keep the current token'
    }

    return 'Optional'
}
