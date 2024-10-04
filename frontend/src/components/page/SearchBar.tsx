import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { SearchIcon } from 'lucide-react'
import * as React from 'react'
import { ReactElement } from 'react'
import { UseNavigateResult } from '@tanstack/react-router'

export function SearchBar({
    search,
    name,
    onSearch,
    children,
}: {
    search?: string
    name: string
    onSearch: (search: string) => void
    children?: ReactElement
}) {
    return (
        <div>
            <div className="flex w-full max-w-sm items-center space-x-2">
                <Input
                    type="search"
                    placeholder={`Search ${name}...`}
                    value={search}
                    onChange={(e) => onSearch(e.target.value)}
                />
                <Button type="submit">
                    <SearchIcon className="h-4 w-4 mr-2" />
                    Search
                </Button>
                {children}
            </div>
        </div>
    )
}

export function navigateOnSearch(navigate: UseNavigateResult<string>) {
    return (search: string) => {
        navigate({
            to: '.',
            search: (prev) => ({
                ...prev,
                filters: {
                    ...prev.filters,
                    search,
                },
            }),
        })
    }
}
