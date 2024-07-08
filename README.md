# Cli7zip - PHP Wrapper for 7zip

## Description

This is a simple wrapper around the CLI binary of [7-zip](https://www.7-zip.org/). I tried to abstract the most commonly used commands like **verify**, **extract**, **create** and **add**. It will try to find the `7zz` binary automatically on your system. I chose to use `7zz` because it's statically linked and standalone thus perfect for shipping without any dependencies. If it can't find `7zz` on your system it will default to the bundled binary from the `./bin` folder. I only bundle the `Linux x86_64` for now. You also have the possibility to point to additional paths in which the library should look for the binary via the constructor of the `Cli7zip` class.

Because we use the full version we have support for all archive formats, not only `.7z`. Taken from the website of 7-zip it shuold support the following formats:

-   Packing / unpacking: 7z, XZ, BZIP2, GZIP, TAR, ZIP and WIM
-   Unpacking only: APFS, AR, ARJ, CAB, CHM, CPIO, CramFS, DMG, EXT, FAT, GPT, HFS, IHEX, ISO, LZH, LZMA, MBR, MSI, NSIS, NTFS, QCOW2, RAR, RPM, SquashFS, UDF, UEFI, VDI, VHD, VHDX, VMDK, XAR and Z

## Prerequisites

-   PHP >= 8.2

## Install

-   `composer require codebites/cli7zip`

## Usage

```PHP
<?php

use Codebites\Cli7zip\Cli7zip;

$cli7zip = new Cli7zip();

// Extract archive without folder creation
$success = $cli7zip->extractArchive('/my/archive.7z', '/my/existing/target/folder');

// Extract archive with folder creation
$success = $cli7zip->extractArchive('/my/archive.7z', '/my/non-existing/target/folder', true);

// Compress directory as 7z
$success = $cli7zip->compressDir('/my/folder/to/compress', '/my/archive.7z', '7z');

// Compress directory as Zip
$archivePath = $cli7zip->compressDir('/my/folder/to/compress', '/my/archive.zip', 'zip');

// Add files to existing archive
$success = $cli7zip->addFilesToArchive('/my/archive.7z', '/my/first/file.txt', '/my/seconde/file2.txt');
```

## Warning

**DO NOT US IN PRODUCTION!** This is highly experimental and not battle-tested. I wrote this for a use-case I had. If there is any interest I will try to continue to improve this library.

## License Information

This project includes binaries from [7-zip](https://www.7-zip.org) downloaded from [Github](https://github.com/ip7z/7zip/releases/latest). No modifications are made.
The 7-zip binaries are distributed under the GNU Lesser General Public License (LGPL). The license is included in this repository.

-   The 7-zip binaries are located in the `bin` directory.
-   The LGPL license can be found in `licenses/LGPL-3.0.txt`.
