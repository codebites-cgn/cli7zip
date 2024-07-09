<?php

namespace Codebites\Cli7zip\Tests;

use Codebites\Cli7zip\Cli7zip;
use Codebites\Cli7zip\Path;
use Codebites\Cli7zip\Temp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class Cli7zTest extends TestCase
{
    private string $tmpDir;
    private string $testArchive;

    public function setUp(): void
    {
        $this->tmpDir = Temp::createFolder();
        $this->testArchive = Path::join($this->tmpDir, 'archive.7z');
        $sevenZipBinary = Cli7zip::getBundledBinaryPath();

        if ($sevenZipBinary !== null) {
            $testFiles = [
                Path::join($this->tmpDir, 'testfile.txt') => 'Hello, World!',
                Path::join($this->tmpDir, 'testfile2.txt') => 'This is from PHPUnit!',
            ];

            foreach ($testFiles as $path => $content) {
                file_put_contents($path, $content);
            }

            // Create test archive
            $process = new Process([$sevenZipBinary, 'a', $this->testArchive, ...array_keys($testFiles)]);
            $process->run();
            if (!$process->isSuccessful()) {
                print("Error creating Test Archive!" . PHP_EOL);
                print($process->getOutput() . PHP_EOL);
            }

            // Remove test files
            foreach ($testFiles as $path => $content) {
                unlink($path);
            }
        }
    }

    public function tearDown(): void
    {
        Path::removeDir($this->tmpDir);
    }

    public function testConstructorThrowsNoExceptionForMissingBinaryParameter(): void
    {
        $this->expectNotToPerformAssertions();
        new Cli7zip();
    }

    public function testArchiveIntegrity(): void
    {
        $cli7z = new Cli7zip();
        $this->assertTrue($cli7z->testArchiveIntegrity($this->testArchive));
    }

    public function testExtractToValidDirectoryWithoutCreation(): void
    {
        $cli7z = new Cli7zip();
        $extractionDir = Path::join($this->tmpDir, 'extracted_files');
        mkdir($extractionDir, 0775, true);

        $this->assertTrue($cli7z->extractArchive($this->testArchive, $extractionDir, false));
        $this->assertTrue(Path::exists(Path::join($extractionDir, 'testfile.txt')));
        $this->assertTrue(Path::exists(Path::join($extractionDir, 'testfile2.txt')));

        Path::removeDir($extractionDir);
    }

    public function testExtractToValidDirectoryWithCreation(): void
    {
        $cli7z = new Cli7zip();
        $extractionDir = Path::join($this->tmpDir, 'extracted_files');
        $file1 = Path::join($extractionDir, 'testfile.txt');
        $file2 = Path::join($extractionDir, 'testfile2.txt');

        $this->assertTrue($cli7z->extractArchive($this->testArchive, $extractionDir, true));
        $this->assertTrue(Path::exists($file1));
        $this->assertTrue(Path::exists($file2));

        Path::removeDir($extractionDir);
    }

    public function testCompressFolder(): void
    {
        $cli7z = new Cli7zip();
        $newArchive = Path::join($this->tmpDir, 'archive2.7z');

        $this->assertNotNull($cli7z->compressDir($this->tmpDir, $newArchive));
        $this->assertTrue(Path::exists($newArchive));

        unlink($newArchive);
    }

    public function testAddStringToArchive(): void
    {
        $cli7z = new Cli7zip();

        $this->assertTrue($cli7z->addStringToArchive($this->testArchive, 'Hello, World!', 'addedfile.txt'));

        $extractionDir = Path::join($this->tmpDir, 'extracted_files');
        $this->assertTrue($cli7z->extractArchive($this->testArchive, $extractionDir, true));
        $this->assertTrue(Path::exists(Path::join($extractionDir, 'addedfile.txt')));
        $this->assertEquals('Hello, World!', file_get_contents(Path::join($extractionDir, 'addedfile.txt')));

        Path::removeDir($extractionDir);
    }
}
