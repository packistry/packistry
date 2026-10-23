<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Repository;
use App\Models\Upstream;
use App\Models\UpstreamCredential;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Upstream
 */
class UpstreamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'only' => $this->only ?? [],
            'exclude' => $this->exclude ?? [],
            'metadata_url' => $this->metadata_url,
            'last_error' => $this->last_error,
            'last_error_at' => $this->last_error_at,
            'private' => $this->whenLoaded('credentials', fn (): bool => $this->isPrivate()),
            'credentials' => $this->whenLoaded('credentials', fn (): array => $this->credentials
                ->map(fn (UpstreamCredential $credential): array => [
                    'id' => $credential->id,
                    'host' => $credential->host,
                    'type' => $credential->type,
                    'username' => $credential->username,
                ])
                ->values()
                ->all()),
            'repositories' => $this->whenLoaded('repositories', fn (): array => $this->repositories
                ->map(fn (Repository $repository): array => [
                    'id' => $repository->id,
                    'name' => $repository->name,
                ])
                ->values()
                ->all()),
            'packages_count' => $this->whenCounted('packages'),
            'dists_count' => $this->whenCounted('dists'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
