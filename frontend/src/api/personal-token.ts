import { z } from 'zod'
import { del, get, post } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'

export const personalToken = z.object({
    id: z.coerce.string(),
    name: z.string(),
    abilities: z.string().array().nullable(),
    lastUsedAt: z.coerce.date().nullable(),
    expiresAt: z.coerce.date().nullable(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type PersonalToken = z.infer<typeof personalToken>

export const personalTokenQuery = paginatedQuery({
    filters: z
        .object({
            search: z.string().optional(),
        })
        .optional(),
})

export type PersonalTokenQuery = z.infer<typeof personalTokenQuery>
export const paginatedPersonalToken = paginated(personalToken)

export type PaginatedPersonalToken = z.infer<typeof paginatedPersonalToken>

export function fetchPersonalTokens(query: PersonalTokenQuery) {
    return get(paginatedPersonalToken, `/personal-tokens?${toQueryString(query)}`)
}

export const storePersonalTokenInput = z.object({
    name: z.string(),
    abilities: z.string().array(),
    expiresAt: z.coerce.date().nullable(),
})

export type StorePersonalTokenInput = z.infer<typeof storePersonalTokenInput>

export function storePersonalToken(input: z.infer<typeof storePersonalTokenInput>) {
    return post(
        z.object({
            token: personalToken,
            plainText: z.string(),
        }),
        '/personal-tokens',
        input
    )
}

export function deletePersonalToken(tokenId: string) {
    return del(personalToken, `/personal-tokens/${tokenId}`)
}
