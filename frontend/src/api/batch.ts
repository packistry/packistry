import { z } from 'zod'
import { del, get } from '@/api/axios'
import { packageSchema } from '@/api/package'

export const batch = z.object({
    id: z.coerce.string(),
    name: z.string(),
    package: packageSchema.optional(),
    totalJobs: z.number(),
    failedJobs: z.number(),
    pendingJobs: z.number(),
    processedJobs: z.number(),
    progress: z.number(),
    createdAt: z.coerce.date(),
    finishedAt: z.coerce.date().nullable(),
    cancelledAt: z.coerce.date().nullable(),
})

export type Batch = z.infer<typeof batch>

export function fetchBatches() {
    return get(batch.array(), '/batches')
}

export function pruneBatches() {
    return del(z.string(), '/batches')
}
