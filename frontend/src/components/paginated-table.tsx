import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Pagination } from '@/components/pagination'
import * as React from 'react'
import { ReactNode } from 'react'
import { Skeleton } from '@/components/ui/skeleton'
import { DefinedQueryObserverResult, UseQueryResult } from '@tanstack/react-query'
import { AnyPaginated } from '@/api'
import FailedToLoad from '@/components/failed-to-load'
import { Empty, EmptyProps } from '@/components/Empty'
import { Permission } from '@/permission'
import { useAuth } from '@/auth'
import { ChevronDown, ChevronUp, MinusIcon } from 'lucide-react'
import { cn } from '@/lib/utils'
import { UseNavigateResult } from '@tanstack/react-router'

export type PaginatedUseQueryResult = UseQueryResult<AnyPaginated>
type InferReturn<T> = T extends DefinedQueryObserverResult<{ data: { id: string }[] }> ? T['data']['data'][0] : never

type BaseColumn = {
    label: string
    head?: React.ThHTMLAttributes<HTMLTableCellElement>
    cell?: React.ThHTMLAttributes<HTMLTableCellElement>
    skeleton?: ReactNode
    permission?: Permission
    sorter?: boolean
    sort?: 'asc' | 'desc'
}

type UnknownColumn<Query extends PaginatedUseQueryResult> = {
    key: string
    render: (row: InferReturn<Query>) => ReactNode
} & BaseColumn

type NamedColumn<Query extends PaginatedUseQueryResult> = {
    key: keyof InferReturn<Query>
    render?: (row: InferReturn<Query>) => ReactNode
} & BaseColumn

type Column<Query extends PaginatedUseQueryResult> = UnknownColumn<Query> | NamedColumn<Query>

export type PaginatedTableProps<Query extends PaginatedUseQueryResult> = {
    query: Query
    sort?: string
    onSort?: (column: Column<Query>) => unknown
    columns: Column<Query>[]
    empty?: EmptyProps
}

export function PaginatedTable<Query extends PaginatedUseQueryResult>({
    query,
    sort,
    onSort,
    empty,
    ...rest
}: PaginatedTableProps<Query>) {
    const { can } = useAuth()
    const columns = rest.columns
        .filter((column) => {
            if (typeof column.permission === 'undefined') {
                return true
            }

            return can(column.permission)
        })
        .map((column) => {
            if (typeof sort === 'undefined') {
                return column
            }

            const direction = sort.startsWith('-') ? 'desc' : 'asc'

            if (sort.substring(direction === 'desc' ? 1 : 0, sort.length) === column.key) {
                return {
                    ...column,
                    sort: direction as 'asc' | 'desc',
                }
            }

            return {
                ...column,
                sort: undefined,
            }
        })

    if (query.isPending) {
        const loaders = Array(10).fill(0)

        return (
            <Table>
                <TableHeaderFromColumns
                    columns={columns}
                    onSort={onSort}
                />
                <TableBody>
                    {loaders.map((_, index) => {
                        return (
                            <TableLoadingRow
                                columns={columns}
                                key={index}
                            />
                        )
                    })}
                </TableBody>
            </Table>
        )
    }

    if (query.isError) {
        return <FailedToLoad />
    }

    if (typeof empty !== 'undefined' && query.data?.data.length === 0) {
        return (
            <div>
                <Empty
                    className="mt-24"
                    {...empty}
                />
            </div>
        )
    }

    return (
        <>
            <Table>
                <TableHeaderFromColumns
                    columns={columns}
                    onSort={onSort}
                />
                <TableBody>
                    {query.data.data.map((row) => (
                        <TableRow key={row.id}>
                            {columns.map((column) => {
                                return (
                                    <TableCell
                                        {...column.cell}
                                        key={String(column.key)}
                                    >
                                        {!column.render ? row[column.key] : column.render(row)}
                                    </TableCell>
                                )
                            })}
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
            {query.data && <Pagination meta={query.data.meta} />}
        </>
    )
}

function TableHeaderFromColumns({
    columns,
    onSort,
}: {
    columns: Column<PaginatedUseQueryResult>[]
    onSort?: (column: Column<PaginatedUseQueryResult>) => unknown
}) {
    return (
        <TableHeader>
            <TableRow>
                {columns.map((column) => (
                    <TableHead
                        key={String(column.key)}
                        {...column.head}
                    >
                        <button
                            className={cn({
                                'flex justify-between w-full cursor-pointer h-12 items-center': column.sorter,
                            })}
                            onClick={() => {
                                if (typeof onSort !== 'undefined') {
                                    onSort(column)
                                }
                            }}
                        >
                            <span>{column.label}</span>
                            {column.sorter && (
                                <span>
                                    {typeof column.sort === 'undefined' && <MinusIcon size={20} />}
                                    {column.sort === 'asc' && <ChevronUp size={20} />}
                                    {column.sort === 'desc' && <ChevronDown size={20} />}
                                </span>
                            )}
                        </button>
                    </TableHead>
                ))}
            </TableRow>
        </TableHeader>
    )
}

function TableLoadingRow({ columns }: { columns: Column<never>[] }) {
    return (
        <TableRow>
            {columns.map((column) => (
                <TableCell key={String(column.key)}>
                    {column.skeleton ? column.skeleton : <Skeleton className="h-4 w-full" />}
                </TableCell>
            ))}
        </TableRow>
    )
}

export function navigateOnSort(navigate: UseNavigateResult<string>, param: string = 'sort') {
    return (column: Column<never>) => {
        if (!column.sorter) {
            return
        }

        let sort: undefined | typeof column.key = column.key

        if (column.sort === 'desc') {
            sort = undefined
        }

        if (column.sort === 'asc') {
            sort = '-' + String(column.key)
        }

        navigate({
            to: '.',
            search: (prev) => ({
                ...prev,
                [param]: sort,
            }),
        })
    }
}
