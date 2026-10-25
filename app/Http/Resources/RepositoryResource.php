<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Repository;
use App\Models\Upstream;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Repository
 */
class RepositoryResource extends JsonResource
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
            'path' => $this->path,
            'description' => $this->description,
            'public' => $this->public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'url' => $this->url(),
            'packages_count' => $this->whenCounted('packages'),
            'upstreams' => $this->whenLoaded('upstreams', fn (): array => $this->upstreams
                ->map(fn (Upstream $upstream): array => [
                    'id' => $upstream->id,
                    'name' => $upstream->name,
                    'priority' => $upstream->pivot?->priority,
                    'enabled' => $upstream->pivot?->enabled,
                ])
                ->values()
                ->all()),
        ];
    }
}
