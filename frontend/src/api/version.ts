import { z } from 'zod'
import { paginated, paginatedQuery, toQueryString } from '@/api/pagination'
import { get } from '@/api/axios'

export const versionSchema = z.object({
    id: z.coerce.string(),
    name: z.string(),
    totalDownloads: z.number().optional(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type Version = z.infer<typeof versionSchema>

export const versionQuery = paginatedQuery({
    filters: z.object({
        search: z.string().optional(),
    }),
    sort: z.enum(['totalDownloads', '-totalDownloads', 'name', '-name', 'createdAt', '-createdAt']),
})

export type VersionQuery = z.infer<typeof versionQuery>
export const paginatedVersion = paginated(versionSchema)

export type PaginatedVersion = z.infer<typeof paginatedVersion>

export function fetchPackageVersions(packageId: string | number, query: VersionQuery) {
    return get(paginatedVersion, `/packages/${packageId}/versions?${toQueryString(query)}`)
}
