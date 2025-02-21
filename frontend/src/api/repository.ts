import { z } from 'zod'
import { del, get, patch, post } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'

export const repository = z.object({
    id: z.coerce.string(),
    name: z.string(),
    path: z.string().nullable(),
    description: z.string().nullable(),
    public: z.boolean(),
    url: z.string(),
    packagesCount: z.number().optional(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type Repository = z.infer<typeof repository>

export const repositoryQuery = paginatedQuery({
    filters: z.object({
        search: z.string().optional(),
        public: z.boolean().optional(),
    }),
    sort: z.enum(['name', '-name', 'path', '-path', 'packagesCount', '-packagesCount']),
})

export type RepositoryQuery = z.infer<typeof repositoryQuery>
export const paginatedRepository = paginated(repository)

export type PaginatedRepository = z.infer<typeof paginatedRepository>
export function fetchRepositories(query: RepositoryQuery) {
    return get(paginatedRepository, `/repositories?${toQueryString(query)}`)
}

export const storeRepositoryInput = z.object({
    name: z.string(),
    path: z.string(),
    description: z.string(),
    public: z.boolean(),
})

export function storeRepository(input: z.infer<typeof storeRepositoryInput>) {
    return post(repository, '/repositories', input)
}

export const updateRepositoryInput = z.object({
    id: z.string(),
    name: z.string(),
    path: z.string(),
    description: z.string(),
    public: z.boolean(),
})

export function updateRepository({ id, ...input }: z.infer<typeof updateRepositoryInput>) {
    return patch(repository, `/repositories/${id}`, input)
}

export function deleteRepository(repositoryId: string) {
    return del(repository, `/repositories/${repositoryId}`)
}
