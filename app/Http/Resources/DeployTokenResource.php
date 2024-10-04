<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DeployToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
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
