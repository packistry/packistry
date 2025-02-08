import { z } from 'zod'

export const versionSchema = z.object({
    id: z.coerce.string(),
    name: z.string(),
    downloadsCount: z.number().optional(),
    createdAt: z.coerce.date(),
    updatedAt: z.coerce.date(),
})

export type Version = z.infer<typeof versionSchema>
