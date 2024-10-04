<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Package;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Package */
class ComposerPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'minified' => 'composer/2.0',
            'packages' => [
                $this->name => $this
                    ->versions
                    ->map(fn (Version $version) => [
                        ...$version->metadata,
                        'name' => $this->name,
                        'version' => $version->name,
                        'type' => 'library',
                        'time' => $version->created_at,
                        'dist' => [
                            'type' => 'zip',
                            'url' => url("$this->name/$version->name"),
                            'shasum' => $version->shasum,
                        ],
                    ]),
            ],
        ];
    }
}
