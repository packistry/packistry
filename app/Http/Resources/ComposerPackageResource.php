<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\ConstraintValidator;
use App\Models\Package;
use App\Models\Version;
use Composer\MetadataMinifier\MetadataMinifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Package */
class ComposerPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $constraintValidator = new ConstraintValidator;

        // Composer rejects the whole package when one version holds an unparsable constraint, so skip that version
        $versions = $this->versions
            ->filter(fn (Version $version): bool => $constraintValidator->invalidConstraints($version->metadata) === [])
            ->map(fn (Version $version) => [
                ...$version->metadata,
                'name' => $this->name,
                'version' => $version->name,
                'type' => $this->type,
                'time' => $version->created_at,
                'dist' => [
                    'type' => 'zip',
                    'url' => $this->repository->url("/$this->name/$version->name"),
                    'shasum' => $version->shasum,
                ],
            ])
            ->all();

        return [
            'minified' => 'composer/2.0',
            'packages' => [
                $this->name => MetadataMinifier::minify(array_values($versions)),
            ],
        ];
    }
}
