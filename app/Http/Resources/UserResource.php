<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'email' => $this->email,
            'role' => $this->role,
            'authentication_source' => new AuthenticationSourceResource($this->whenLoaded('authenticationSource')),
            'permissions' => $this->role->permissions(),
            'repositories' => RepositoryResource::collection($this->whenLoaded('repositories')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
