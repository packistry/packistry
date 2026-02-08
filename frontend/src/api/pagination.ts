import { z } from 'zod'
import { decamelize, decamelizeKeys } from 'humps'

export function paginatedQuery<
    FilterType extends z.ZodTypeAny = z.ZodUndefined,
    SortType extends z.ZodTypeAny = z.ZodUndefined,
    IncludeType extends z.ZodTypeAny = z.ZodUndefined,
>({ filters, sort, include }: { filters?: FilterType; sort?: SortType; include?: IncludeType }) {
    return z.object({
        page: z.number().optional(),
        size: z.number().optional(),
        include: include ? include.array().optional() : z.string().array().optional(),
        sort: sort ? sort.optional() : z.undefined().optional(),
        filters: filters ? filters.optional() : z.undefined().optional(),
    })
}

export const query = paginatedQuery({
    filters: z.record(z.string(), z.string().or(z.number()).or(z.boolean())).optional(),
    sort: z.string(),
})

export type Query = z.infer<typeof query>

export const meta = z.object({
    currentPage: z.number(),
    from: z.number().nullable(),
    lastPage: z.number(),
    path: z.string(),
    perPage: z.number(),
    to: z.number().nullable(),
    total: z.number(),
    links: z
        .object({
            url: z.string().nullable(),
            label: z.string(),
            active: z.boolean(),
        })
        .array(),
})

export type Meta = z.infer<typeof meta>

export function paginated<ItemType extends z.ZodTypeAny>(schema: ItemType) {
    return z.object({
        data: z.array(schema),
        meta: meta,
    })
}

export const anyPaginated = paginated(z.any())

export type AnyPaginated = z.infer<typeof anyPaginated>

type QueryParams = {
    filters?: Record<string, string | number | string[] | undefined | boolean>
    sort?: unknown
    page?: number
    size?: number
    include?: unknown[] | string[]
}

export function toQueryString({ filters, sort, ...props }: QueryParams) {
    return new URLSearchParams({
        ...(decamelizeKeys(props) as Record<string, string>),
        ...(filters ? buildFilters(filters) : {}),
        ...(sort ? { sort: decamelize(String(sort)) } : {}),
    }).toString()
}

export function buildFilters(
    filters: Record<string, string | number | string[] | undefined | boolean>
): Record<string, string> {
    return Object.keys(filters).reduce(
        (carry, key) => {
            const value = filters[key]

            if (typeof value === 'undefined') {
                return carry
            }

            carry[`filter[${decamelize(key)}]`] = Array.isArray(value) ? value.join(',') : String(value)

            return carry
        },
        {} as Record<string, string>
    )
}
