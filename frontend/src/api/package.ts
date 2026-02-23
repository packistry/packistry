import { z } from 'zod'
import { del, get, post } from '@/api/axios'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'
import { versionSchema } from '@/api/version'
import { repository } from '@/api/repository'
import { source } from '@/api/source'

export const packageSchema = z.object({
    id: z.coerce.string(),
    name: z.string(),
    repositoryId: z.number(),
    repository: repository.optional(),
    source: source.optional().nullable(),
    description: z.string().nullable(),
    totalDownloads: z.number(),
    latestVersion: z.string().nullable(),
    versions: versionSchema.array().optional(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type Package = z.infer<typeof packageSchema>

export const packageQuery = paginatedQuery({
    filters: z.object({
        repositoryId: z.string().optional(),
        search: z.string().optional(),
    }),
    sort: z.enum(['totalDownloads', '-totalDownloads', 'name', '-name']),
    include: z.enum(['repository']),
})

export type PackageQuery = z.infer<typeof packageQuery>
export const paginatedPackage = paginated(packageSchema)

export type PaginatedPackage = z.infer<typeof paginatedPackage>

export function fetchPackages(query: PackageQuery) {
    return get(paginatedPackage, `/packages?${toQueryString(query)}`)
}

export function fetchPackage(packageId: string | number) {
    return get(packageSchema, `/packages/${packageId}`)
}

export const downloadsPerDate = z.object({ date: z.string(), downloads: z.number() })
export type DownloadsPerDate = z.infer<typeof downloadsPerDate>

export function fetchPackageDownloads(packageId: string | number) {
    return get(downloadsPerDate.array(), `/packages/${packageId}/downloads`)
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

export function rebuildPackage(packageId: string) {
    return post(packageSchema, `/packages/${packageId}/rebuild`, {})
}
