import { z } from 'zod'
import { GitFork, GithubIcon, GitlabIcon } from 'lucide-react'

export const GITEA = 'gitea'
export const GITLAB = 'gitlab'
export const GITHUB = 'github'
export const BITBUCKET = 'bitbucket'

export const sourceProviders = [GITHUB, GITEA, GITLAB, BITBUCKET] as const
export const sourceProvider = z.enum(sourceProviders)

export type SourceProvider = z.infer<typeof sourceProvider>

export const providerUrls: Record<SourceProvider, string> = {
    gitea: 'https://gitea.com',
    github: 'https://api.github.com',
    gitlab: 'https://gitlab.com',
    bitbucket: 'https://api.bitbucket.org',
}

export const providerNames: Record<SourceProvider, string> = {
    gitea: 'Gitea',
    github: 'GitHub',
    gitlab: 'GitLab',
    bitbucket: 'Bitbucket',
}

export const providerIcons: Record<SourceProvider, typeof GithubIcon> = {
    github: GithubIcon,
    gitlab: GitlabIcon,
    gitea: GitFork,
    bitbucket: GitFork,
}

export const providerColors: Record<SourceProvider, string> = {
    github: 'bg-[#24292e] text-white',
    gitlab: 'bg-[#e2492f] text-white',
    gitea: 'bg-[#609926] text-white',
    bitbucket: 'bg-[#0052cc] text-white',
}
