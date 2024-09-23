<?php

declare(strict_types=1);

namespace App\Traits;

use App\Exceptions\VersionNotFoundException;

trait NormalizesVersion
{
    /**
     * @throws VersionNotFoundException
     */
    public function normalizeVersion(string $version): string
    {
        if (! str_starts_with($version, 'dev-')) {
            if (preg_match('/\d+\.\d+\.\d+/', $version, $matches) === false) {
                throw new VersionNotFoundException;
            }

            return $matches[0];
        }

        return $version;
    }
}
