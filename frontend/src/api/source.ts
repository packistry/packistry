import { z } from 'zod'
import { del, get, patch, post } from '@/api/axios'

const provider = z.enum(['gitlab', 'github', 'gitea'])

export type Provider = z.infer<typeof provider>

export const source = z.object({
    id: z.coerce.string(),
    name: z.string(),
    provider: provider,
    url: z.string(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type Source = z.infer<typeof source>

export function fetchSources() {
    return get(source.array(), '/sources')
}

export const storeSourceInput = z.object({
    name: z.string(),
    provider: provider,
    url: z.string(),
    token: z.string(),
})

export function storeSource(input: z.infer<typeof storeSourceInput>) {
    return post(source, '/sources', input)
}

export const updateSourceInput = z.object({
    id: z.string(),
    name: z.string(),
    provider: provider,
    url: z.string(),
    token: z.string().optional(),
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
