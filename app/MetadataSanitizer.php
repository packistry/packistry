<?php

declare(strict_types=1);

namespace App;

use Composer\Semver\VersionParser;
use UnexpectedValueException;

class MetadataSanitizer
{
    /**
     * @var list<string>
     */
    private const array LINK_KEYS = [
        'require',
        'require-dev',
        'conflict',
        'provide',
        'replace',
    ];

    /**
     * Removes hard version constraints Composer is unable to parse. A single
     * unparsable constraint on any version causes Composer to reject the
     * package as a whole, making every other version uninstallable as well.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function sanitize(array $metadata): array
    {
        $parser = new VersionParser;

        foreach (self::LINK_KEYS as $key) {
            if (! array_key_exists($key, $metadata)) {
                continue;
            }

            if (! is_array($metadata[$key])) {
                unset($metadata[$key]);

                continue;
            }

            $metadata[$key] = array_filter(
                $metadata[$key],
                fn (mixed $constraint): bool => is_string($constraint) && self::isParsable($parser, $constraint),
            );
        }

        return $metadata;
    }

    private static function isParsable(VersionParser $parser, string $constraint): bool
    {
        // Composer resolves self.version before parsing, see ArrayLoader::parseLinks()
        if ($constraint === 'self.version') {
            return true;
        }

        try {
            $parser->parseConstraints($constraint);
        } catch (UnexpectedValueException) {
            return false;
        }

        return true;
    }
}
