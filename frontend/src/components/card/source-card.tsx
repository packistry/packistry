import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { EditSourceDialog } from '@/components/dialog/edit-source-dialog'
import { Button } from '@/components/ui/button'
import { ExternalLinkIcon, GitFork, GithubIcon, GitlabIcon } from 'lucide-react'
import * as React from 'react'
import { Source } from '@/api'

const providerIcons = {
    github: GithubIcon,
    gitlab: GitlabIcon,
    gitea: GitFork,
    bitbucket: GitFork,
}

const providerColors = {
    github: 'bg-[#24292e] text-white',
    gitlab: 'bg-[#e2492f] text-white',
    gitea: 'bg-[#609926] text-white',
    bitbucket: 'bg-[#0052CC] text-white',
}

export function SourceCard({ source }: { source: Source }) {
    const ProviderIcon = providerIcons[source.provider] || GitlabIcon

    return (
        <Card
            key={source.id}
            className="overflow-hidden"
        >
            <CardContent className="p-0">
                <div className={`p-4 ${providerColors[source.provider] || 'bg-primary text-primary-foreground'}`}>
                    <div className="flex justify-between items-center">
                        <h3 className="font-semibold text-lg">{source.name}</h3>
                        <ProviderIcon className="h-6 w-6" />
                    </div>
                    <p className="text-sm mt-1 opacity-90">{source.url}</p>
                </div>
                <div className="p-4">
                    <div className="flex justify-between items-center mb-4">
                        <Badge
                            variant="outline"
                            className="text-xs"
                        >
                            {source.provider.toUpperCase()}
                        </Badge>
                        <span className="text-sm text-muted-foreground">Token: ••••••••</span>
                    </div>
                    <div className="flex space-x-2">
                        <EditSourceDialog
                            source={source}
                            trigger={
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="w-full"
                                >
                                    Manage
                                </Button>
                            }
                        />
                        <a
                            href={source.url}
                            className="w-full"
                            rel="noreferrer"
                            target="_blank"
                            tabIndex={-1}
                        >
                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full"
                            >
                                <ExternalLinkIcon className="h-4 w-4 mr-2" />
                                Open
                            </Button>
                        </a>
                    </div>
                </div>
            </CardContent>
        </Card>
    )
}
