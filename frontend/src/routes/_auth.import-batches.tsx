import * as React from 'react'
import { createFileRoute, useNavigate } from '@tanstack/react-router'
import { useBatches, usePruneBatches } from '@/api/hooks'
import { Heading } from '@/components/page/heading'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { z } from 'zod'
import { ImportBatchCard } from '@/components/card/import-batch-card'
import { Empty } from '@/components/empty'
import { DownloadIcon } from 'lucide-react'

export const Route = createFileRoute('/_auth/import-batches')({
    validateSearch: z.object({
        rate: z.number().min(100).optional().default(1000),
        live: z.boolean().default(true),
    }),
    component: ImportBatchesComponent,
})

function ImportBatchesComponent() {
    const { rate, live } = Route.useSearch()
    const navigate = useNavigate()

    const query = useBatches({ refetchInterval: live ? rate : undefined })
    const mutation = usePruneBatches()
    return (
        <>
            <Heading title="Import Batches">
                <div className="flex items-center gap-3">
                    <Button
                        variant="outline"
                        onClick={() => mutation.mutate()}
                        dangerous={{
                            title: 'Prune batches?',
                            description: 'Are you sure you want to prune all existing batches?',
                            confirm: {
                                loading: mutation.isPending,
                            },
                        }}
                    >
                        Prune
                    </Button>
                    <span className="text-sm text-muted-foreground">Live updates:</span>
                    <Switch
                        checked={live}
                        onCheckedChange={() => {
                            navigate({
                                to: '.',
                                search: {
                                    rate,
                                    live: !live,
                                },
                            })
                        }}
                        aria-label="Toggle live updates"
                    />

                    {live ? (
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-muted-foreground">Poll rate:</span>
                            <Select
                                value={rate.toString()}
                                onValueChange={(value) =>
                                    navigate({
                                        to: '.',
                                        search: {
                                            rate: Number(value),
                                            live,
                                        },
                                    })
                                }
                            >
                                <SelectTrigger className="w-[130px] h-8">
                                    <SelectValue placeholder="Select rate" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1000">1 second</SelectItem>
                                    <SelectItem value="3000">3 seconds</SelectItem>
                                    <SelectItem value="5000">5 seconds</SelectItem>
                                    <SelectItem value="10000">10 seconds</SelectItem>
                                    <SelectItem value="30000">30 seconds</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    ) : (
                        <Button
                            loading={query.isRefetching}
                            onClick={() => query.refetch()}
                        >
                            Refresh
                        </Button>
                    )}

                    {live && (
                        <Badge
                            variant="outline"
                            className="bg-green-500/10 text-green-500 border-green-500/20"
                        >
                            <span className="relative flex h-2 w-2 mr-1.5">
                                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                                <span className="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            Live
                        </Badge>
                    )}
                </div>
            </Heading>
            {query.data?.map((batch) => (
                <ImportBatchCard
                    key={batch.id}
                    batch={batch}
                />
            ))}
            {!query.isPending && query.data?.length === 0 && (
                <Empty
                    className="mt-24"
                    title="No Import Batches"
                    description="There are currently no packages being imported"
                    icon={<DownloadIcon />}
                />
            )}
        </>
    )
}
