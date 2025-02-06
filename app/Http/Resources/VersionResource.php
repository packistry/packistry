<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Version
 */
class VersionResource extends JsonResource
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
            'package_id' => $this->package_id,
            'name' => $this->name,
            'shasum' => $this->shasum,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
