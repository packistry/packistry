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
class AuthenticationSourceResource extends JsonResource
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
            'provider' => $this->provider,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'discovery_url' => $this->discovery_url,
            'default_user_role' => $this->default_user_role,
            'repositories' => RepositoryResource::collection($this->whenLoaded('repositories')),
            'packages' => PackageResource::collection($this->whenLoaded('packages')),
            'callback_url' => $this->callbackUrl(),
            'active' => $this->active,
            'allow_registration' => $this->allow_registration,
            'allowed_domains' => $this->allowed_domains,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
