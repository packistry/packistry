<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AuthenticationSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin AuthenticationSource
 */
class PublicAuthenticationSourceResource extends JsonResource
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
            'icon_url' => $this->icon_url,
            'redirect_url' => $this->redirectUrl(),
        ];
    }
}
