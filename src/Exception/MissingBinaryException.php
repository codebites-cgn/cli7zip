<?php

namespace Codebites\Cli7zip\Exception;

use Exception;

class MissingBinaryException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            sprintf('Could not find 7z binary on your system.')
        );
    }
}
