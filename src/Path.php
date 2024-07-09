<?php

namespace Codebites\Cli7zip;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Helper class for working with paths.
 * @author Marco Kretz <marco@codebites.de>
 */
final class Path
{
    /**
     * Joins multiple paths together using the system's directory separator.
     *
     * @param string ...$paths The paths to be joined.
     * @return string The joined path.
     */
    public static function join(string ...$paths): string
    {
        return implode(DIRECTORY_SEPARATOR, array_map(fn ($path) => rtrim($path, DIRECTORY_SEPARATOR), $paths));
    }

    /**
     * Checks if a path or file exists.
     *
     * @param string $path The path or file to check.
     * @return bool True if the path or file exists, false otherwise.
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Checks if a path or file is writable.
     *
     * @param string $path The path or file to check.
     * @return bool True if the path or file is writable, false otherwise.
     */
    public static function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Checks if a path or file is executable.
     *
     * @param string $path The path or file to check.
     * @return bool True if the path or file is executable, false otherwise.
     */
    public static function isExecutable(string $path): bool
    {
        return is_executable($path);
    }

    /**
     * Removes a directory recursively.
     * @url https://stackoverflow.com/a/3349792
     *
     * @param string $dir The directory to remove.
     */
    public static function removeDir(string $dir): bool
    {
        if (!self::exists($dir)) {
            return false;
        }

        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        return rmdir($dir);
    }
}
