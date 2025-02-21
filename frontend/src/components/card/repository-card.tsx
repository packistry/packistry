import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { LockIcon, UnlockIcon } from 'lucide-react'
import { EditRepositoryDialog } from '@/components/dialog/edit-repository-dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { Repository } from '@/api'
import { RepositoryBadge } from '@/components/badge/repository-badge'
import { CopyCommandTooltip } from '@/components/ui/tooltip'

export function RepositoryCard({ repository, className }: { repository: Repository; className?: string }) {
    const command = `composer config repositories.packistry composer ${repository.url}`
    return (
        <Card className={className}>
            <CardHeader className="">
                <div className="flex flex-row items-center justify-between space-y-0">
                    <CardTitle className="font-semibold text-lg">{repository.name}</CardTitle>
                    {!repository.public ? (
                        <LockIcon className="h-4 w-4 text-muted-foreground" />
                    ) : (
                        <UnlockIcon className="h-4 w-4 text-muted-foreground" />
                    )}
                </div>
                <p className="text-xs text-muted-foreground">{repository.description || '-'}</p>
            </CardHeader>
            <CardContent>
                <div className="flex justify-between items-center">
                    <span className="text-2xl font-bold">
                        {typeof repository.packagesCount !== 'undefined' ? (
                            <>{repository.packagesCount} packages</>
                        ) : null}
                    </span>
                    <RepositoryBadge repository={repository} />
                </div>
                <div className="flex items-center mt-4 gap-2">
                    <EditRepositoryDialog
                        repository={repository}
                        trigger={
                            <Button
                                variant="outline"
                                className="w-full"
                                size="sm"
                            >
                                Manage
                            </Button>
                        }
                    />

                    <CopyCommandTooltip command={command} />
                </div>
            </CardContent>
        </Card>
    )
}
