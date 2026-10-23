<?php

declare(strict_types=1);

namespace App\Mirror;

use Carbon\CarbonInterface;
use Composer\MetadataMinifier\MetadataMinifier;

readonly class MetadataResult
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    private function __construct(
        public MetadataStatus $status,
        public ?array $data = null,
        public ?CarbonInterface $modifiedAt = null,
        public bool $stale = false,
    ) {
        //
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function found(array $data, ?CarbonInterface $modifiedAt = null): self
    {
        return new self(MetadataStatus::FOUND, $data, $modifiedAt);
    }

    /**
     * Cached metadata served because the upstream could not be reached.
     */
    public function asStale(): self
    {
        return new self($this->status, $this->data, $this->modifiedAt, stale: true);
    }

    public static function notFound(): self
    {
        return new self(MetadataStatus::NOT_FOUND);
    }

    public static function unavailable(): self
    {
        return new self(MetadataStatus::UNAVAILABLE);
    }

    /**
     * @phpstan-assert-if-true !null $this->data
     */
    public function isFound(): bool
    {
        return $this->status === MetadataStatus::FOUND;
    }

    public function isNotFound(): bool
    {
        return $this->status === MetadataStatus::NOT_FOUND;
    }

    public function isUnavailable(): bool
    {
        return $this->status === MetadataStatus::UNAVAILABLE;
    }

    /**
     * The (expanded) version entries of the package.
     *
     * @return array<int, array<string, mixed>>
     */
    public function versions(string $name): array
    {
        $versions = $this->data['packages'][$name] ?? [];

        if (! is_array($versions)) {
            return [];
        }

        if (($this->data['minified'] ?? null) === 'composer/2.0') {
            $versions = MetadataMinifier::expand(array_values($versions));
        }

        return array_values(array_filter($versions, is_array(...)));
    }
}
