import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { ChevronDown } from 'lucide-react'
import * as React from 'react'
import { useRepositories } from '@/api/hooks'
import { Repository } from '@/api'

export type RepositoryDropdownMenuProps = {
    selected?: string
    onRepoSelect: (repo?: Repository) => void
}

export function RepositoryDropdownMenu({ onRepoSelect, selected }: RepositoryDropdownMenuProps) {
    const query = useRepositories({
        // @todo add an all option?
        size: 1000,
    })

    const repository = (query.data?.data || []).find((repo) => repo.id === selected)

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline">
                    {typeof repository === 'undefined' ? 'All repositories' : (repository.name ?? 'Root')}
                    <ChevronDown className="ml-2 h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent>
                <DropdownMenuItem
                    key="all"
                    onSelect={() => onRepoSelect(undefined)}
                >
                    All repositories
                </DropdownMenuItem>
                {(query.data?.data || []).map((repo) => (
                    <DropdownMenuItem
                        key={repo.id}
                        onSelect={() => onRepoSelect(repo)}
                    >
                        {repo.name ?? 'Root'}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
