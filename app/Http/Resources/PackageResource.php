<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Package
 */
class PackageResource extends JsonResource
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
            'repository_id' => $this->repository_id,
            'source_id' => $this->source_id,
            'upstream_id' => $this->upstream_id,
            'mirror_status' => $this->mirror_status,
            'provider_id' => $this->provider_id,
            'name' => $this->name,
            'type' => $this->type,
            'latest_version' => $this->latest_version,
            'versions' => VersionResource::collection($this->whenLoaded('versions')),
            'repository' => new RepositoryResource($this->whenLoaded('repository')),
            'source' => new SourceResource($this->whenLoaded('source')),
            'upstream' => $this->whenLoaded('upstream', fn (): ?array => $this->upstream === null ? null : [
                'id' => $this->upstream->id,
                'name' => $this->upstream->name,
            ]),
            'description' => $this->description,
            'total_downloads' => $this->total_downloads,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
