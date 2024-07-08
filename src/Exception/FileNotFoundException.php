<?php

namespace Codebites\Cli7zip\Exception;

use Exception;

class FileNotFoundException extends Exception
{
    public function __construct(string $fileOrDirectory)
    {
        parent::__construct(
            sprintf('File or directory "%s" not found.', $fileOrDirectory)
        );
    }
}
