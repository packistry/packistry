import { z } from 'zod'

export const OIDC = 'oidc'
export const GITHUB = 'github'
export const GITLAB = 'gitlab'
export const BITBUCKET = 'bitbucket'
export const GOOGLE = 'google'

export const authenticationProviders = [OIDC, GITHUB, GITLAB, BITBUCKET, GOOGLE] as const
export const authenticationProvider = z.enum(authenticationProviders)

export type AuthenticationProvider = z.infer<typeof authenticationProvider>

export const providerNames: Record<AuthenticationProvider, string> = {
    oidc: 'OpenID Connect',
    github: 'GitHub',
    gitlab: 'GitLab',
    bitbucket: 'Bitbucket',
    google: 'Google',
}

export const providerIcons: Record<AuthenticationProvider, string> = {
    oidc: '',
    github: 'https://github.com/favicon.ico',
    gitlab: 'https://gitlab.com/favicon.ico',
    bitbucket: 'https://bitbucket.org/favicon.ico',
    google: 'https://google.com/favicon.ico',
}
