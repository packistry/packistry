import { z } from 'zod'
import { del, get, patch, post, put } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'
import { repository } from '@/api/repository'

export const PACKAGIST_URL = 'https://repo.packagist.org'

// Packagist dists are mostly GitHub zipballs, a token for the GitHub API raises its anonymous rate limit
export const GITHUB_API_HOST = 'api.github.com'

export function isPackagist(upstream: Pick<Upstream, 'url'>): boolean {
    return upstream.url.replace(/\/+$/, '') === PACKAGIST_URL
}

export const upstreamCredentialTypes = ['http-basic', 'bearer', 'header'] as const

export const upstreamCredentialType = z.enum(upstreamCredentialTypes)

export type UpstreamCredentialType = z.infer<typeof upstreamCredentialType>

export const upstreamCredentialTypeNames: Record<UpstreamCredentialType, string> = {
    'http-basic': 'HTTP Basic',
    bearer: 'Bearer token',
    header: 'Custom header',
}

export const upstreamCredential = z.object({
    id: z.coerce.string(),
    host: z.string(),
    type: upstreamCredentialType,
    username: z.string().nullable(),
})

export type UpstreamCredential = z.infer<typeof upstreamCredential>

export const upstream = z.object({
    id: z.coerce.string(),
    name: z.string(),
    url: z.string(),
    only: z.string().array(),
    exclude: z.string().array(),
    metadataUrl: z.string().nullable(),
    lastError: z.string().nullable(),
    lastErrorAt: z.coerce.date().nullable(),
    private: z.boolean().optional(),
    credentials: upstreamCredential.array().optional(),
    repositories: z
        .object({
            id: z.coerce.string(),
            name: z.string(),
        })
        .array()
        .optional(),
    packagesCount: z.number().optional(),
    distsCount: z.number().optional(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type Upstream = z.infer<typeof upstream>

export const upstreamQuery = paginatedQuery({
    filters: z
        .object({
            search: z.string().optional(),
        })
        .optional(),
    sort: z.enum(['name', '-name', 'url', '-url', 'packagesCount', '-packagesCount']),
})

export type UpstreamQuery = z.infer<typeof upstreamQuery>
export const paginatedUpstream = paginated(upstream)

export type PaginatedUpstream = z.infer<typeof paginatedUpstream>

export function fetchUpstreams(query: UpstreamQuery) {
    return get(paginatedUpstream, `/upstreams?${toQueryString(query)}`)
}

export const upstreamCredentialInput = z.object({
    id: z.string().optional(),
    host: z.string(),
    type: upstreamCredentialType,
    username: z.string().nullable(),
    secret: z.string().optional(),
})

export type UpstreamCredentialInput = z.infer<typeof upstreamCredentialInput>

export const storeUpstreamInput = z.object({
    name: z.string(),
    url: z.string(),
    only: z.string().array(),
    exclude: z.string().array(),
    credentials: upstreamCredentialInput.array(),
})

export type StoreUpstreamInput = z.infer<typeof storeUpstreamInput>

export function storeUpstream(input: StoreUpstreamInput) {
    return post(upstream, '/upstreams', input)
}

export const updateUpstreamInput = storeUpstreamInput.extend({
    id: z.string(),
})

export type UpdateUpstreamInput = z.infer<typeof updateUpstreamInput>

export function updateUpstream({ id, ...input }: UpdateUpstreamInput) {
    return patch(upstream, `/upstreams/${id}`, input)
}

export function deleteUpstream(upstreamId: string) {
    return del(upstream, `/upstreams/${upstreamId}`)
}

export const upstreamTestResult = z.object({
    ok: z.boolean(),
    metadataUrl: z.string().nullable(),
    error: z.string().nullable(),
})

export function testUpstream(upstreamId: string) {
    return post(upstreamTestResult, `/upstreams/${upstreamId}/test`, {})
}

export const syncRepositoryUpstreamsInput = z.object({
    repositoryId: z.string(),
    upstreams: z
        .object({
            id: z.string(),
            enabled: z.boolean(),
        })
        .array(),
})

export type SyncRepositoryUpstreamsInput = z.infer<typeof syncRepositoryUpstreamsInput>

export function syncRepositoryUpstreams({ repositoryId, ...input }: SyncRepositoryUpstreamsInput) {
    return put(repository, `/repositories/${repositoryId}/upstreams`, input)
}
