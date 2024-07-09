<?php

namespace Codebites\Cli7zip;

use Codebites\Cli7zip\Exception\FileNotFoundException;
use Codebites\Cli7zip\Exception\InsufficientPermissionsException;
use Codebites\Cli7zip\Exception\MissingBinaryException;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Class Cli7zip which acts as a wrapper for the 7zz binary.
 * @author Marco Kretz <marco@codebites.de>
 */
class Cli7zip
{
    /**
     * Path to the 7zz binary.
     *
     * @var null|string
     */
    private ?string $sevenZipBinary;

    /**
     * Constructor.
     *
     * @throws MissingBinaryException
     */
    public function __construct(string $executableName = '7zz', array $additionalPaths = [])
    {
        $exeFinder = new ExecutableFinder();
        $sevenZipBinary = $exeFinder->find($executableName, null, $additionalPaths);
        if ($sevenZipBinary === null) {
            $this->sevenZipBinary = self::getBundledBinaryPath();
            if ($this->sevenZipBinary === null) {
                throw new MissingBinaryException($executableName);
            }
        }

        if (!$this->isBinarySevenZip($this->sevenZipBinary)) {
            throw new RuntimeException(sprintf(
                'The "%s" binary could not be found or is not executable.',
                $this->sevenZipBinary
            ));
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
     * @throws InsufficientPermissionsException
     */
    public function extractArchive(string $archiveToExtract, string $targetFolder, bool $createParents = false): bool
    {
        if (!Path::exists($archiveToExtract)) {
            throw new FileNotFoundException($archiveToExtract);
        }

        if (!Path::exists($targetFolder)) {
            if ($createParents) {
                if (!mkdir($targetFolder, 0775, true) && !is_dir($targetFolder)) {
                    throw new RuntimeException(sprintf('Directory "%s" was not created', $targetFolder));
                }
            } else {
                throw new FileNotFoundException($targetFolder);
            }
        }

        if (!Path::isWritable($targetFolder)) {
            throw new InsufficientPermissionsException($targetFolder, 'writable');
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
        $process = new Process([$this->sevenZipBinary, "-t$format", 'a', $outputArchive, $directoryToCompress]);
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
     * Add a string to an existing archive.
     * $ 7zz a $existingArchive $stringToAdd
     *
     * @param string $existingArchive The existing archive.
     * @param string $stringToAdd The string to add.
     * @param string $filename The filename of the string.
     * @return bool True if the string was added successfully, false otherwise.
     *
     * @throws FileNotFoundException
     */
    public function addStringToArchive(string $existingArchive, string $stringToAdd, string $filename): bool
    {
        if (!Path::exists($existingArchive)) {
            throw new FileNotFoundException($existingArchive);
        }

        // Write string to temporary file
        $tmpFolder =  Temp::createFolder();
        $stringFile = Path::join($tmpFolder, $filename);
        file_put_contents($stringFile, $stringToAdd);

        $process = new Process([$this->sevenZipBinary, 'a', $existingArchive, $stringFile]);
        $process->run();

        // Remove temporary stuff
        unlink($stringFile);
        rmdir($tmpFolder);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }

    /**
     * Add an empty directory to an existing archive.
     * $ 7zz a $existingArchive $directoryName/
     *
     * @param string $existingArchive
     * @param string $directoryName
     * @return bool
     *
     * @throws FileNotFoundException
     */
    public function addEmptyDirectoryToArchive(string $existingArchive, string $directoryName): bool
    {
        if (!Path::exists($existingArchive)) {
            throw new FileNotFoundException($existingArchive);
        }

        $tmpFolder =  Temp::createFolder();
        $directoryName = Path::join($tmpFolder, $directoryName);
        if (!mkdir($directoryName, 0775) && !is_dir($directoryName)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directoryName));
        }

        $process = new Process([$this->sevenZipBinary, 'a', $existingArchive, rtrim($directoryName, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR]);
        $process->run();
        Path::removeDir($tmpFolder);
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

        return (Path::exists($binPath) && Path::isExecutable($binPath)) ? $binPath : null;
    }

    /**
     * Checks if the given path is a valid 7zip binary.
     *
     * @param string $binaryPath The path to check.
     * @return bool True if the path is a valid 7zip binary, false otherwise.
     */
    private function isBinarySevenZip(string $binaryPath): bool
    {
        if (!Path::exists($binaryPath) || !Path::isExecutable($binaryPath)) {
            return false;
        }

        $process = new Process([$binaryPath, '--help']);
        $process->run();
        if (!$process->isSuccessful()) {
            return false;
        }

        $output = $process->getOutput();

        return str_contains($output, '7-Zip') !== false;
    }
}
