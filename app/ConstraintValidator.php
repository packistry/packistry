<?php

declare(strict_types=1);

namespace App;

use Composer\Semver\VersionParser;
use UnexpectedValueException;

class ConstraintValidator
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

    private readonly VersionParser $parser;

    /**
     * Parse results per constraint, versions of a package mostly share the same constraints
     *
     * @var array<string, bool>
     */
    private array $parsable = [];

    public function __construct()
    {
        $this->parser = new VersionParser;
    }

    /**
     * Returns the links whose constraint Composer is unable to parse, e.g. "require.laravel/framework".
     * A single unparsable constraint on any version makes Composer reject the package as a whole.
     *
     * @param  array<array-key, mixed>  $metadata
     * @return list<string>
     */
    public function invalidConstraints(array $metadata): array
    {
        $invalid = [];

        foreach (self::LINK_KEYS as $key) {
            if (! array_key_exists($key, $metadata)) {
                continue;
            }

            if (! is_array($metadata[$key])) {
                $invalid[] = $key;

                continue;
            }

            foreach ($metadata[$key] as $target => $constraint) {
                if (! $this->isParsable($constraint)) {
                    $invalid[] = "$key.$target";
                }
            }
        }

        return $invalid;
    }

    private function isParsable(mixed $constraint): bool
    {
        if (! is_string($constraint)) {
            return false;
        }

        // Composer resolves self.version before parsing, see ArrayLoader::createLink()
        if ($constraint === 'self.version') {
            return true;
        }

        return $this->parsable[$constraint] ??= $this->parses($constraint);
    }

    private function parses(string $constraint): bool
    {
        try {
            $this->parser->parseConstraints($constraint);
        } catch (UnexpectedValueException) {
            return false;
        }

        return true;
    }
}
