import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { EditRepositoryDialog } from '@/components/dialog/edit-repository-dialog'
import { Button } from '@/components/ui/button'
import * as React from 'react'
import { Repository } from '@/api'
import { RepositoryBadge } from '@/components/badge/repository-badge'
import { CopyCommandTooltip } from '@/components/ui/tooltip'
import { useAuth } from '@/auth'
import { REPOSITORY_UPDATE, UPSTREAM_READ } from '@/permission'
import { RepositoryUpstreamsDialog } from '@/components/dialog/repository-upstreams-dialog'

export function RepositoryCard({ repository, className }: { repository: Repository; className?: string }) {
    const { can } = useAuth()

    const command = `composer config repositories.packistry composer ${repository.url}`
    return (
        <Card className={className}>
            <CardHeader className="">
                <div className="flex flex-row items-center justify-between space-y-0">
                    <CardTitle className="font-semibold text-lg">{repository.name}</CardTitle>
                    <CopyCommandTooltip command={command} />
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
                {can(REPOSITORY_UPDATE) && (
                    <div className="flex items-center mt-4 gap-2">
                        {/* only when the upstreams are loaded, saving would otherwise detach them */}
                        {can(UPSTREAM_READ) && typeof repository.upstreams !== 'undefined' && (
                            <RepositoryUpstreamsDialog
                                repository={repository}
                                trigger={
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        size="sm"
                                    >
                                        Upstreams
                                    </Button>
                                }
                            />
                        )}
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
                    </div>
                )}
            </CardContent>
        </Card>
    )
}
