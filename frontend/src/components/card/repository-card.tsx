import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { LockIcon, UnlockIcon } from 'lucide-react'
import { EditRepositoryDialog } from '@/components/dialog/edit-repository-dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { Repository } from '@/api'
import { RepositoryBadge } from '@/components/badge/repository-badge'

export function RepositoryCard({ repository, className }: { repository: Repository; className?: string }) {
    return (
        <Card className={className}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{repository.name}</CardTitle>
                {!repository.public ? (
                    <LockIcon className="h-4 w-4 text-muted-foreground" />
                ) : (
                    <UnlockIcon className="h-4 w-4 text-muted-foreground" />
                )}
            </CardHeader>
            <CardContent>
                <p className="text-xs text-muted-foreground">{repository.description || '-'}</p>
                <div className="flex justify-between items-center mt-2">
                    <span className="text-2xl font-bold">
                        {typeof repository.packagesCount !== 'undefined' ? (
                            <>{repository.packagesCount} packages</>
                        ) : null}
                    </span>
                    <RepositoryBadge repository={repository} />
                </div>
                <EditRepositoryDialog
                    repository={repository}
                    trigger={
                        <Button
                            variant="outline"
                            className="mt-4 w-full"
                        >
                            Manage
                        </Button>
                    }
                />
            </CardContent>
        </Card>
    )
}
