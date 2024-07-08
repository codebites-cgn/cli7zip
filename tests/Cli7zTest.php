<?php

namespace Codebites\Cli7zip\Tests;

use Codebites\Cli7zip\Cli7zip;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class Cli7zTest extends TestCase
{
    private static string $tmpDir;
    private static string $testArchive;

    public static function setUpBeforeClass(): void
    {
        self::$tmpDir = sys_get_temp_dir() . '/' . uniqid("seven-zipper-test-");
        $sevenZipBinary = Cli7zip::getBundledBinaryPath();
        if ($sevenZipBinary !== null) {
            mkdir(self::$tmpDir, 0775, true);
            $testArchive = self::$tmpDir . '/archive.7z';
            $testFiles = [
                self::$tmpDir . '/testfile.txt' => 'Hello, World!',
                self::$tmpDir . '/testfile2.txt' => 'This is from PHPUnit!',
            ];

            foreach ($testFiles as $path => $content) {
                print("Creating test file: $path" . PHP_EOL);
                file_put_contents($path, $content,);
            }

            print("Creating test archive: $testArchive" . PHP_EOL);
            $process = new Process([$sevenZipBinary, 'a', $testArchive, ...array_keys($testFiles)]);
            $process->run();
            if (!$process->isSuccessful()) {
                print("Error creating Test Archive!" . PHP_EOL);
                print($process->getOutput() . PHP_EOL);
            } else {
                self::$testArchive = $testArchive;
            }

            foreach ($testFiles as $path => $content) {
                unlink($path);
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (file_exists(self::$tmpDir)) {
            unlink(self::$testArchive);
            rmdir(self::$tmpDir);
        }
    }

    public function testConstructorThrowsNoExceptionForMissingBinaryParameter(): void
    {
        $this->expectNotToPerformAssertions();

        new Cli7zip();
    }

    public function testArchiveIntegrity(): void
    {
        $cli7z = new Cli7zip();
        $this->assertTrue($cli7z->testArchiveIntegrity(self::$testArchive));
    }

    public function testExtractToValidDirectoryWithoutCreation(): void
    {
        $cli7z = new Cli7zip();
        $extractionDir = self::$tmpDir . '/extracted_files';
        mkdir($extractionDir, 0775, true);
        $cli7z->extractArchive(self::$testArchive, $extractionDir, false);
        $this->assertTrue(file_exists($extractionDir . '/testfile.txt'));
        $this->assertTrue(file_exists($extractionDir . '/testfile2.txt'));
        unlink($extractionDir . '/testfile.txt');
        unlink($extractionDir . '/testfile2.txt');
        rmdir($extractionDir);
    }

    public function testExtractToValidDirectoryWithCreation(): void
    {
        $cli7z = new Cli7zip();
        $extractionDir = self::$tmpDir . '/extracted_files2';
        $cli7z->extractArchive(self::$testArchive, $extractionDir, true);
        $this->assertTrue(file_exists($extractionDir . '/testfile.txt'));
        $this->assertTrue(file_exists($extractionDir . '/testfile2.txt'));
        unlink($extractionDir . '/testfile.txt');
        unlink($extractionDir . '/testfile2.txt');
        rmdir($extractionDir);
    }

    public function testCompressFolder(): void
    {
        $cli7z = new Cli7zip();
        $cli7z->compressDir(self::$tmpDir, self::$tmpDir . '/archive2.7z');
        $this->assertTrue(file_exists(self::$tmpDir . '/archive2.7z'));
        unlink(self::$tmpDir . '/archive2.7z');
    }

    public function testAddingFilesToExistingArchive(): void
    {
        $cli7z = new Cli7zip();
    }
}
