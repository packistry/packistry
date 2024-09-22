<?php

declare(strict_types=1);

namespace App\Incoming\Gitea;

class Repository extends Input
{
    /**
     * @param  string[]  $topics
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $fullName,
        public ?string $description,
        public bool $empty,
        public bool $private,
        public bool $fork,
        public bool $template,
        public ?string $parent,
        public bool $mirror,
        public int $size,
        public ?string $language,
        public string $languagesUrl,
        public string $htmlUrl,
        public string $url,
        public ?string $link,
        public string $sshUrl,
        public string $cloneUrl,
        public ?string $originalUrl,
        public ?string $website,
        public int $starsCount,
        public int $forksCount,
        public int $watchersCount,
        public int $openIssuesCount,
        public int $openPrCounter,
        public int $releaseCounter,
        public string $defaultBranch,
        public bool $archived,
        public string $createdAt,
        public string $updatedAt,
        public string $archivedAt,
        public RepositoryPermissions $permissions,
        public bool $hasIssues,
        public InternalTracker $internalTracker,
        public bool $hasWiki,
        public bool $hasPullRequests,
        public bool $hasProjects,
        public string $projectsMode,
        public bool $hasReleases,
        public bool $hasPackages,
        public bool $hasActions,
        public bool $ignoreWhitespaceConflicts,
        public bool $allowMergeCommits,
        public bool $allowRebase,
        public bool $allowRebaseExplicit,
        public bool $allowSquashMerge,
        public bool $allowFastForwardOnlyMerge,
        public bool $allowRebaseUpdate,
        public bool $defaultDeleteBranchAfterMerge,
        public string $defaultMergeStyle,
        public bool $defaultAllowMaintainerEdit,
        public ?string $avatarUrl,
        public bool $internal,
        public ?string $mirrorInterval,
        public string $objectFormatName,
        public string $mirrorUpdated,
        public ?string $repoTransfer,
        public ?array $topics
    ) {}
}
