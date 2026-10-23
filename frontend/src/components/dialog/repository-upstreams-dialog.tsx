import * as React from 'react'
import { ReactNode, useState } from 'react'
import axios from 'axios'
import { ArrowDownIcon, ArrowUpIcon, CloudDownloadIcon, XIcon } from 'lucide-react'
import { Link } from '@tanstack/react-router'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Switch } from '@/components/ui/switch'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Empty } from '@/components/empty'
import { UpstreamBadge } from '@/components/badge/upstream-badge'
import { useSyncRepositoryUpstreams, useUpstreams } from '@/api/hooks'
import { Repository } from '@/api/repository'
import { Upstream } from '@/api/upstream'

type Selected = {
    id: string
    name: string
    enabled: boolean
}

function initialSelection(repository: Repository): Selected[] {
    return [...(repository.upstreams || [])]
        .sort((a, b) => (a.priority ?? 0) - (b.priority ?? 0))
        .map(({ id, name, enabled }) => ({ id, name, enabled: enabled ?? true }))
}

export function RepositoryUpstreamsDialog({ repository, trigger }: { repository: Repository; trigger?: ReactNode }) {
    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const [selected, setSelected] = useState<Selected[]>(() => initialSelection(repository))
    const [error, setError] = useState<string>()
    const mutation = useSyncRepositoryUpstreams()
    const upstreams = useUpstreams({ size: 1000, sort: 'name' }, { enabled: isDialogOpen })

    const available = (upstreams.data?.data || []).filter(
        (upstream) => !selected.some((selection) => selection.id === upstream.id)
    )
    const upstreamsById = new Map((upstreams.data?.data || []).map((upstream) => [upstream.id, upstream]))

    function onOpenChange(open: boolean) {
        if (open) {
            setSelected(initialSelection(repository))
            setError(undefined)
        }

        setIsDialogOpen(open)
    }

    function move(index: number, offset: number) {
        const next = [...selected]
        const [item] = next.splice(index, 1)
        next.splice(index + offset, 0, item)
        setSelected(next)
    }

    function add(upstream: Upstream) {
        setSelected([...selected, { id: upstream.id, name: upstream.name, enabled: true }])
    }

    function save() {
        setError(undefined)

        mutation.mutate(
            {
                repositoryId: repository.id,
                upstreams: selected.map(({ id, enabled }) => ({ id, enabled })),
            },
            {
                onSuccess() {
                    setIsDialogOpen(false)
                },
                onError(error) {
                    const errors = axios.isAxiosError(error) ? error.response?.data?.errors : undefined
                    const messages = errors ? Object.values(errors).flat() : []

                    setError(messages.length > 0 ? String(messages[0]) : 'Failed to save upstreams')
                },
            }
        )
    }

    return (
        <Dialog
            open={isDialogOpen}
            onOpenChange={onOpenChange}
        >
            <DialogTrigger asChild>{trigger ? trigger : <Button variant="ghost">Upstreams</Button>}</DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Upstreams of {repository.name}</DialogTitle>
                    <DialogDescription>
                        Packages that are not in this repository are looked up in these upstreams, from top to bottom.
                        The first upstream that has a package serves it, versions are never merged. Put private
                        registries above public ones.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-2 min-w-0">
                    {selected.length === 0 && (
                        <p className="text-sm text-muted-foreground py-4 text-center">
                            No upstreams, this repository only serves its own packages.
                        </p>
                    )}
                    {selected.map((selection, index) => {
                        const upstream = upstreamsById.get(selection.id)

                        return (
                            <div
                                key={selection.id}
                                className="flex items-center gap-3 rounded-md border p-2 pl-3"
                            >
                                <span className="text-sm text-muted-foreground w-5">{index + 1}.</span>
                                <div className="grow min-w-0">
                                    <p className="font-medium truncate">{selection.name}</p>
                                    {upstream && (
                                        <p className="text-xs text-muted-foreground font-mono truncate">
                                            {upstream.url}
                                        </p>
                                    )}
                                </div>
                                <div className="w-16 flex justify-end">
                                    {upstream && <UpstreamBadge upstream={upstream} />}
                                </div>
                                <Switch
                                    checked={selection.enabled}
                                    onCheckedChange={(enabled) =>
                                        setSelected(
                                            selected.map((item) =>
                                                item.id === selection.id ? { ...item, enabled } : item
                                            )
                                        )
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                >
                                    <ArrowUpIcon className="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    disabled={index === selected.length - 1}
                                    onClick={() => move(index, 1)}
                                >
                                    <ArrowDownIcon className="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setSelected(selected.filter((item) => item.id !== selection.id))}
                                >
                                    <XIcon className="h-4 w-4" />
                                </Button>
                            </div>
                        )
                    })}
                </div>

                {upstreams.isSuccess && upstreams.data.data.length === 0 ? (
                    <Empty
                        icon={<CloudDownloadIcon />}
                        title="No Upstreams"
                        description="Create an upstream first, e.g. Packagist or a GitLab group."
                        button={
                            <Link
                                to="/upstreams"
                                search={{ open: true }}
                            >
                                <Button>
                                    <span className="mr-2">+</span> Add Upstream
                                </Button>
                            </Link>
                        }
                    />
                ) : (
                    <Select
                        value=""
                        onValueChange={(id) => {
                            const upstream = upstreamsById.get(id)

                            if (upstream) {
                                add(upstream)
                            }
                        }}
                    >
                        <SelectTrigger disabled={available.length === 0}>
                            <SelectValue placeholder="Add an upstream..." />
                        </SelectTrigger>
                        <SelectContent>
                            {available.map((upstream) => {
                                const blocked = repository.public && upstream.private

                                return (
                                    <SelectItem
                                        key={upstream.id}
                                        value={upstream.id}
                                        disabled={blocked}
                                    >
                                        {upstream.name}
                                        {blocked ? ' (private, not allowed on public repositories)' : ''}
                                    </SelectItem>
                                )
                            })}
                        </SelectContent>
                    </Select>
                )}

                {repository.public && selected.length > 0 && (
                    <p className="text-sm text-muted-foreground">
                        This repository is public, anyone can download packages from these upstreams through it.
                    </p>
                )}

                {error && <p className="text-sm font-medium text-destructive">{error}</p>}

                <div>
                    <Button
                        onClick={save}
                        loading={mutation.isPending}
                    >
                        Save Upstreams
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    )
}
