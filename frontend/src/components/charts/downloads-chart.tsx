import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts'
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart'
import * as React from 'react'
import { DownloadsPerDate } from '@/api'

export function DownloadsChart({ data }: { data?: DownloadsPerDate[] }) {
    return (
        <ChartContainer
            config={{
                downloads: {
                    label: 'Downloads',
                    color: 'hsl(var(--chart-1))',
                },
            }}
            className="aspect-auto h-[250px] w-full"
        >
            <BarChart
                accessibilityLayer
                data={data || []}
                margin={{
                    left: 12,
                    right: 12,
                }}
            >
                <CartesianGrid vertical={false} />
                <XAxis
                    dataKey="date"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={32}
                    tickFormatter={(value) => {
                        const date = new Date(value)
                        return date.toLocaleDateString(undefined, {
                            month: 'short',
                            day: 'numeric',
                        })
                    }}
                />
                <ChartTooltip
                    content={
                        <ChartTooltipContent
                            className="w-[150px]"
                            nameKey="downloads"
                            labelFormatter={(value) => {
                                return new Date(value).toLocaleDateString(undefined, {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric',
                                })
                            }}
                        />
                    }
                />
                <Bar
                    dataKey="downloads"
                    fill={`var(--color-downloads)`}
                />
            </BarChart>
        </ChartContainer>
    )
}
