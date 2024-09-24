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
            $directory = $this->directoryFromArchive($zip);

            $index = $zip->locateName($directory.'composer.json');

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

    /**
     * @throws FailedToOpenArchiveException
     */
    private function directoryFromArchive(ZipArchive $zip): string
    {
        $directory = $zip->getNameIndex(0);

        if ($directory === false) {
            throw new FailedToOpenArchiveException('failed to determine directory name');
        }

        return str_ends_with($directory, '/')
            ? $directory
            : '';
    }
}
