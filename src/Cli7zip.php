<?php

namespace Codebites\Cli7zip;

use Codebites\Cli7zip\Exception\FileNotFoundException;
use Codebites\Cli7zip\Exception\MissingBinaryException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Cli7zip
{
    private ?string $sevenZipBinary;

    public function __construct(string $executableName = '7zz', array $additionalPaths = [])
    {
        $exeFinder = new ExecutableFinder();
        $sevenZipBinary = $exeFinder->find($executableName, null, $additionalPaths);
        if ($sevenZipBinary === null) {
            $this->sevenZipBinary = self::getBundledBinaryPath();
            if ($this->sevenZipBinary === null) {
                throw new MissingBinaryException();
            }
        }
    }

    /**
     * Test the integrity of an archive.
     * $ 7zz t $archiveToTest
     *
     * @param string $archiveToTest The archive to test.
     * @return bool True if the archive is valid, false otherwise.
     *
     * @throws FileNotFoundException
     * @throws ProcessFailedException
     */
    public function testArchiveIntegrity(string $archiveToTest): bool
    {
        if (!Path::exists($archiveToTest)) {
            throw new FileNotFoundException($archiveToTest);
        }

        $process = new Process([$this->sevenZipBinary, 't', $archiveToTest]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }

    /**
     * Extract an archive.
     * $ 7zz x $archiveToExtract -y -o$targetFolder
     *
     * @param string $archiveToExtract The archive to extract.
     * @param string $targetFolder The folder to extract to.
     * @param bool $createParents If true, the target folder will be created if it doesn't exist.
     * @return bool True if the archive was extracted successfully, false otherwise.
     *
     * @throws FileNotFoundException
     * @throws ProcessFailedException
     */
    public function extractArchive(string $archiveToExtract, string $targetFolder, bool $createParents = false): bool
    {
        if (!Path::exists($archiveToExtract)) {
            throw new FileNotFoundException($archiveToExtract);
        }

        if (!Path::exists($targetFolder)) {
            if ($createParents) {
                mkdir($targetFolder, 0775, true);
            } else {
                throw new FileNotFoundException($targetFolder);
            }
        }

        if (!Path::isWritable($targetFolder)) {
            throw new RuntimeException('Target directory not writable: ' . $targetFolder);
        }

        $process = new Process([$this->sevenZipBinary, 'x', $archiveToExtract, '-y', "-o$targetFolder"]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }

    /**
     * Compress a directory.
     * $ 7zz -t$format a $outputArchive $directoryToCompress
     *
     * @param string $directoryToCompress The directory to compress.
     * @param string $outputArchive The output archive.
     * @param string $format The format of the archive.
     * @return string|null The output archive if successful, null otherwise.
     *
     * @throws FileNotFoundException
     * @throws ProcessFailedException
     */
    public function compressDir(string $directoryToCompress, string $outputArchive, string $format = '7z'): ?string
    {
        if (!Path::exists($directoryToCompress)) {
            throw new FileNotFoundException($directoryToCompress);
        }

        if (Path::exists($outputArchive)) {
            throw new RuntimeException('Archive already exists: ' . $outputArchive);
        }

        $directoryToCompress = rtrim($directoryToCompress, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*';
        $process = new Process([$this->sevenZipBinary, "-t$format", 'a', $outputArchive, "$directoryToCompress"]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputArchive;
    }

    /**
     * Add files to an existing archive.
     * $ 7zz a $existingArchive $filesToAdd
     *
     * @param string $existingArchive The existing archive.
     * @param string ...$filesToAdd The files to add.
     * @return bool True if the files were added successfully, false otherwise.
     *
     * @throws FileNotFoundException
     * @throws ProcessFailedException
     */
    public function addFilesToArchive(string $existingArchive, string ...$filesToAdd): bool
    {
        if (!Path::exists($existingArchive)) {
            throw new FileNotFoundException($existingArchive);
        }

        $process = new Process([$this->sevenZipBinary, 'a', $existingArchive, ...$filesToAdd]);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }

    /**
     * Get the path to the bundled 7zip binary for the current platform.
     *
     * @return string|null The path to the bundled 7zip binary, or null if it doesn't exist.
     */
    public static function getBundledBinaryPath(): ?string
    {
        $osPlatform = strtolower(PHP_OS);
        $osArchitecture = strtolower(php_uname('m'));
        $binPath = Path::join(__DIR__, '..', 'bin', "7zzs_$osPlatform-$osArchitecture");

        return Path::exists($binPath) ? $binPath : null;
    }
}
