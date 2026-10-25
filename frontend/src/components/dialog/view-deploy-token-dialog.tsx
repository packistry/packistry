import * as React from 'react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { DeployToken } from '@/api/deploy-token'
import { RepositoryPackageTree } from '@/components/form/elements/repository-package-tree'
import { DialogProps } from '@radix-ui/react-dialog'
import { TokenStatus } from '@/components/badge/token-status'

type ViewDeployTokenDialogProps = {
    token: DeployToken
    trigger?: React.ReactNode
} & DialogProps

type DeployTokenScopeForm = {
    repositories: string[]
    packages: string[]
}

export function ViewDeployTokenDialog({ token, trigger, ...dialogProps }: ViewDeployTokenDialogProps) {
    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const form = useForm<DeployTokenScopeForm>({
        defaultValues: {
            repositories: token.repositories.map((repository) => repository.id),
            packages: token.packages.map((pkg) => pkg.id),
        },
    })

    const abilities = token.abilities || []

    return (
        <Dialog
            {...dialogProps}
            open={isDialogOpen}
            onOpenChange={setIsDialogOpen}
        >
            <DialogTrigger asChild>{trigger ? trigger : <Button variant="ghost">View Access</Button>}</DialogTrigger>
            <DialogContent className="min-w-[1000px]">
                <DialogHeader>
                    <DialogTitle>Deploy Token Access</DialogTitle>
                </DialogHeader>
                <div className="flex gap-6">
                    <div className="space-y-4 min-w-[470px]">
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Name</p>
                            <p className="font-medium">{token.name}</p>
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Status</p>
                            <TokenStatus token={token} />
                        </div>
                        <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Abilities</p>
                            <div className="flex flex-wrap gap-2">
                                {abilities.length === 0 && <Badge variant="outline">No abilities</Badge>}
                                {abilities.map((ability) => (
                                    <Badge
                                        key={ability}
                                        variant="secondary"
                                    >
                                        {ability}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </div>
                    <div className="space-y-4 grow">
                        <RepositoryPackageTree
                            label="Repositories & Packages"
                            description="Read-only view of this deploy token access scope."
                            control={form.control}
                            packageRepositoryMap={token.packages.reduce<Record<string, string>>((accumulator, pkg) => {
                                accumulator[String(pkg.id)] = String(pkg.repositoryId)
                                return accumulator
                            }, {})}
                            readOnly
                        />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    )
}
