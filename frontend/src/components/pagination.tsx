import * as React from 'react'
import { Meta } from '@/api'
import {
    Pagination as UiPagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination'

export function Pagination({ meta }: { meta: Meta }) {
    return (
        <UiPagination>
            <PaginationContent>
                <PaginationItem>
                    <PaginationPrevious
                        disabled={meta.currentPage === 1}
                        to="."
                        search={(prev) => ({ ...prev, page: (prev.page || 1) - 1 })}
                    />
                </PaginationItem>
                {meta.links.slice(1, -1).map((link, index) => {
                    return (
                        <PaginationItem key={index}>
                            <PaginationLink
                                isActive={link.active}
                                to="."
                                disabled={link.url === null}
                                search={(prev) => ({ ...prev, page: Number(link.label) })}
                            >
                                {link.label}
                            </PaginationLink>
                        </PaginationItem>
                    )
                })}
                <PaginationItem>
                    <PaginationNext
                        disabled={meta.currentPage === meta.lastPage}
                        to="."
                        search={(prev) => ({ ...prev, page: (prev.page || 1) + 1 })}
                    />
                </PaginationItem>
            </PaginationContent>
        </UiPagination>
    )
}
