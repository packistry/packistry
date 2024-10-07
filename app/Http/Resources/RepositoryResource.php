<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Repository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'packages_count' => $this->whenCounted('packages'),
        ];
    }
}
