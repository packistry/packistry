import { z } from 'zod'
import { del, get, post } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'

export const packageSchema = z.object({
    id: z.coerce.string(),
    name: z.string(),
    description: z.string().nullable(),
    downloads: z.number(),
    latestVersion: z.string().nullable(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type Package = z.infer<typeof packageSchema>

export const packageQuery = paginatedQuery({
    filters: z.object({
        repositoryId: z.string().optional(),
        search: z.string().optional(),
    }),
    sort: z.enum(['downloads', '-downloads', 'name', '-name']),
})

export type PackageQuery = z.infer<typeof packageQuery>
export const paginatedPackage = paginated(packageSchema)

export type PaginatedPackage = z.infer<typeof paginatedPackage>

export function fetchPackages(query: PackageQuery) {
    return get(paginatedPackage, `/packages?${toQueryString(query)}`)
}

export const storePackageInput = z.object({
    repository: z.string(),
    source: z.string(),
    projects: z.string().array(),
    webhook: z.boolean(),
})

export function storePackage(input: z.infer<typeof storePackageInput>) {
    return post(packageSchema.array(), '/packages', input)
}

export function deletePackage(packageId: string) {
    return del(packageSchema, `/packages/${packageId}`)
}
