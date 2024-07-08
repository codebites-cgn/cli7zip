<?php

namespace Codebites\Cli7zip;

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
}
