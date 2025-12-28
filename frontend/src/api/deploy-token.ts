import { z } from 'zod'
import { del, get, post } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'

export const deployToken = z.object({
    id: z.coerce.string(),
    name: z.string(),
    abilities: z.string().array().nullable(),
    lastUsedAt: z.coerce.date().nullable(),
    expiresAt: z.coerce.date().nullable(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type DeployToken = z.infer<typeof deployToken>

export const deployTokenQuery = paginatedQuery({
    filters: z
        .object({
            search: z.string().optional(),
        })
        .optional(),
    sort: z.enum(['name', '-name', 'expiresAt', '-expiresAt', 'lastUsedAt', '-lastUsedAt']),
})

export type DeployTokenQuery = z.infer<typeof deployTokenQuery>
export const paginatedDeployToken = paginated(deployToken)

export type PaginatedDeployToken = z.infer<typeof paginatedDeployToken>

export function fetchDeployTokens(query: DeployTokenQuery) {
    return get(paginatedDeployToken, `/deploy-tokens?${toQueryString(query)}`)
}

export const storeDeployTokenInput = z.object({
    name: z.string(),
    abilities: z.string().array(),
    expiresAt: z.coerce.date().nullable(),
    repositories: z.string().array(),
    packages: z.string().array().optional(),
})

export type StoreDeployTokenInput = z.infer<typeof storeDeployTokenInput>

export function storeDeployToken(input: z.infer<typeof storeDeployTokenInput>) {
    return post(
        z.object({
            token: deployToken,
            plainText: z.string(),
        }),
        '/deploy-tokens',
        input
    )
}

export function deleteDeployToken(tokenId: string) {
    return del(deployToken.omit({ abilities: true, expiresAt: true, lastUsedAt: true }), `/deploy-tokens/${tokenId}`)
}
