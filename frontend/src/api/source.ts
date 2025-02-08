import { z } from 'zod'
import { del, get, patch, post } from '@/api/axios'

const provider = z.enum(['gitlab', 'github', 'gitea', 'bitbucket'])

export type Provider = z.infer<typeof provider>

const baseSource = z.object({
    id: z.coerce.string(),
    name: z.string(),
    url: z.string(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export const source = z.discriminatedUnion('provider', [
    z.object({
        provider: z.literal('gitlab'),
        metadata: z.object({}),
        ...baseSource.shape,
    }),
    z.object({
        provider: z.literal('github'),
        metadata: z.object({}),
        ...baseSource.shape,
    }),
    z.object({
        provider: z.literal('gitea'),
        metadata: z.object({}),
        ...baseSource.shape,
    }),
    z.object({
        provider: z.literal('bitbucket'),
        metadata: z.object({
            workspace: z.string().optional(),
        }),
        ...baseSource.shape,
    }),
])

export type Source = z.infer<typeof source>

export function fetchSources() {
    return get(source.array(), '/sources')
}

export const storeSourceInput = z.object({
    name: z.string(),
    provider: provider,
    url: z.string(),
    token: z.string(),
    metadata: z.any(),
})

export function storeSource(input: z.infer<typeof storeSourceInput>) {
    return post(source, '/sources', input)
}

export const updateSourceInput = z.object({
    id: z.string(),
    name: z.string(),
    url: z.string(),
    token: z.string().optional(),
    metadata: z.any(),
})

export function updateSource(input: z.infer<typeof updateSourceInput>) {
    return patch(source, `/sources/${input.id}`, input)
}

export function deleteSource(sourceId: string) {
    return del(source, `/sources/${sourceId}`)
}

export const sourceProject = z.object({
    id: z.coerce.string(),
    name: z.string(),
    fullName: z.string(),
    url: z.string(),
    webUrl: z.string(),
})

export function fetchSourceProjects(source: string, search?: string) {
    return get(sourceProject.array(), `/sources/${source}/projects?search=${search}`)
}
