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

export type PaginatedUseQueryResult = UseQueryResult<AnyPaginated>
type InferReturn<T> = T extends DefinedQueryObserverResult<{ data: { id: string }[] }> ? T['data']['data'][0] : never

type BaseColumn = {
    label: string
    head?: React.ThHTMLAttributes<HTMLTableCellElement>
    cell?: React.ThHTMLAttributes<HTMLTableCellElement>
    skeleton?: ReactNode
    permission?: Permission
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
    columns: Column<Query>[]
    empty?: EmptyProps
}

export function PaginatedTable<Query extends PaginatedUseQueryResult>({
    query,
    empty,
    ...rest
}: PaginatedTableProps<Query>) {
    const { can } = useAuth()
    const columns = rest.columns.filter((column) => {
        if (typeof column.permission === 'undefined') {
            return true
        }

        return can(column.permission)
    })

    if (query.isPending) {
        const loaders = Array(10).fill(0)

        return (
            <Table>
                <TableHeaderFromColumns columns={columns} />
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
                <TableHeaderFromColumns columns={columns} />
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

function TableHeaderFromColumns({ columns }: { columns: Column<PaginatedUseQueryResult>[] }) {
    return (
        <TableHeader>
            <TableRow>
                {columns.map((column) => (
                    <TableHead
                        key={String(column.key)}
                        {...column.head}
                    >
                        {column.label}
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
