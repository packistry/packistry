import { z } from 'zod'
import { del, get, patch, post } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'
import { authenticationProvider } from '@/api/authentication-provider'
import { repository } from '@/api/repository'
import { role } from '@/api/role'

const publicAuthenticationSource = z.object({
    id: z.coerce.string(),
    name: z.string(),
    iconUrl: z.string().nullable(),
    redirectUrl: z.string(),
})

export const authenticationSource = z.object({
    id: z.coerce.string(),
    name: z.string(),
    iconUrl: z.string().nullable(),
    provider: authenticationProvider,
    defaultUserRole: role,
    repositories: repository.array().optional(),
    clientId: z.string(),
    clientSecret: z.string(),
    discoveryUrl: z.string().nullable(),
    callbackUrl: z.string(),
    active: z.boolean(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type AuthenticationSource = z.infer<typeof authenticationSource>
export type PublicAuthenticationSource = z.infer<typeof publicAuthenticationSource>

export const authenticationSourceQuery = paginatedQuery({
    filters: z
        .object({
            search: z.string().optional(),
        })
        .optional(),
    sort: z.enum(['name', '-name', 'active', '-active']),
})

export type AuthenticationSourceQuery = z.infer<typeof authenticationSourceQuery>
export const paginatedAuthenticationSource = paginated(authenticationSource)

export type PaginatedAuthenticationSource = z.infer<typeof paginatedAuthenticationSource>

export function fetchAuthenticationSources(query: AuthenticationSourceQuery) {
    return get(paginatedAuthenticationSource, `/authentication-sources?${toQueryString(query)}`)
}

export function fetchPublicAuthenticationSources() {
    return get(publicAuthenticationSource.array(), '/auths')
}

export const storeAuthenticationSourceInput = z.object({
    name: z.string(),
    iconUrl: z.string().nullable(),
    provider: authenticationProvider,
    defaultUserRepositories: z.string().array(),
    defaultUserRole: role,
    clientId: z.string(),
    clientSecret: z.string(),
    discoveryUrl: z.string().nullable(),
    active: z.boolean(),
})

export type StoreAuthenticationSourceInput = z.infer<typeof storeAuthenticationSourceInput>

export function storeAuthenticationSource(input: z.infer<typeof storeAuthenticationSourceInput>) {
    return post(authenticationSource, '/authentication-sources', input)
}

export const updateAuthenticationSourceInput = z.object({
    id: z.string(),
    name: z.string(),
    iconUrl: z.string().nullable(),
    provider: authenticationProvider,
    defaultUserRepositories: z.string().array(),
    defaultUserRole: role,
    clientId: z.string(),
    clientSecret: z.string(),
    discoveryUrl: z.string().nullable(),
    active: z.boolean(),
})

export type UpdateAuthenticationSourceInput = z.infer<typeof updateAuthenticationSourceInput>

export function updateAuthenticationSource(input: z.infer<typeof updateAuthenticationSourceInput>) {
    return patch(authenticationSource, `/authentication-sources/${input.id}`, input)
}

export function deleteAuthenticationSource(sourceId: string) {
    return del(authenticationSource, `/authentication-sources/${sourceId}`)
}
