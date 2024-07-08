<?php

namespace Codebites\Cli7zip;

/**
 * Helper class for temporary files and folders.
 * @author Marco Kretz <marco@codebites.de>
 */
final class Temp
{
    /**
     * Create a temporary folder.
     *
     * @return string|null The path to the temporary folder or null if an error occurred.
     */
    public static function createFolder(): ?string
    {
        // Create temporary file
        $tmpFile = tempnam(sys_get_temp_dir(), 'cli7zip');
        if (!$tmpFile) {
            return null;
        }
        unlink($tmpFile);

        // Create temporary folder
        $tmpFolder =  "$tmpFile.dir";
        if (!mkdir($tmpFolder, 0775)) {
            return null;
        }

        return $tmpFolder;
    }
}
