<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeployToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin DeployToken
 */
class DeployTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<int|string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'repositories' => $this->whenLoaded('repositories', fn (): array => $this->repositories
                ->map(fn ($repository): array => [
                    'id' => $repository->id,
                    'name' => $repository->name,
                ])
                ->toArray()),
            'packages' => $this->whenLoaded('packages', fn (): array => $this->packages
                ->map(fn ($package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'repository_id' => $package->repository_id,
                ])
                ->toArray()),
            $this->mergeWhen($this->relationLoaded('token'), fn (): array => [
                'abilities' => $this->token?->abilities,
                'last_used_at' => $this->token?->last_used_at,
                'expires_at' => $this->token?->expires_at,
            ]),
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
