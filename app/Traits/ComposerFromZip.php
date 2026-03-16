<?php

declare(strict_types=1);

namespace App\Traits;

use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToOpenArchiveException;
use ZipArchive;

trait ComposerFromZip
{
    /**
     * @return array<string, mixed>
     *
     * @throws ComposerJsonNotFoundException|FailedToOpenArchiveException
     */
    private function decodedComposerJsonFromZip(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new FailedToOpenArchiveException("failed to open archive $path");
        }

        try {
            $index = $this->composerJsonIndexFromArchive($zip);

            if ($index === false) {
                return throw new ComposerJsonNotFoundException('composer.json not found in archive');
            }

            $content = $zip->getFromIndex($index);

            if ($content === false) {
                return throw new ComposerJsonNotFoundException('composer.json not found in archive');
            }

            $decoded = json_decode($content, true);

            if ($decoded === false) {
                return throw new ComposerJsonNotFoundException('composer.json not found in archive');
            }

            return $decoded;
        } finally {
            $zip->close();
        }
    }

    private function composerJsonIndexFromArchive(ZipArchive $zip): int|false
    {
        $rootIndex = $zip->locateName('composer.json');

        if ($rootIndex !== false) {
            return $rootIndex;
        }

        $topLevelDirectory = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if ($entry === false) {
                continue;
            }

            $entry = ltrim($entry, '/');

            if ($entry === '' || str_starts_with($entry, '__MACOSX/')) {
                continue;
            }

            $topLevel = explode('/', $entry)[0];

            if ($topLevelDirectory === null) {
                $topLevelDirectory = $topLevel;
            } elseif ($topLevelDirectory !== $topLevel) {
                return false;
            }
        }

        if ($topLevelDirectory === null) {
            return false;
        }

        return $zip->locateName($topLevelDirectory.'/composer.json');
    }
}
