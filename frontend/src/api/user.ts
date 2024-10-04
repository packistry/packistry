import { z } from 'zod'
import { del, get, patch, post } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'
import { permission } from '@/permission'
import { repository } from '@/api/repository'

export const role = z.enum(['admin', 'user'])
export const user = z.object({
    id: z.coerce.string(),
    name: z.string(),
    email: z.string(),
    role,
    repositories: repository.array().optional(),
    permissions: permission.array(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type User = z.infer<typeof user>

export const userQuery = paginatedQuery({
    filters: z
        .object({
            search: z.string().optional(),
        })
        .optional(),
})

export type UserQuery = z.infer<typeof userQuery>
export const paginatedUser = paginated(user)

export type PaginatedUser = z.infer<typeof paginatedUser>

export function fetchUsers(query: UserQuery) {
    return get(paginatedUser, `/users?${toQueryString(query)}`)
}

export const storeUserInput = z.object({
    name: z.string(),
    email: z.string(),
    role,
    repositories: z.string().array(),
})

export function storeUser(input: z.infer<typeof storeUserInput>) {
    return post(user, '/users', input)
}

export const updateUserInput = z.object({
    id: z.string(),
    name: z.string(),
    email: z.string(),
    role,
    repositories: z.string().array(),
})

export function updateUser({ id, ...input }: z.infer<typeof updateUserInput>) {
    return patch(user, `/users/${id}`, input)
}

export function deleteUser(id: string) {
    return del(user, `/users/${id}`)
}
