<?php

namespace Codebites\Cli7zip\Exception;

use Exception;

class MissingBinaryException extends Exception
{
    public function __construct(string $binaryName)
    {
        parent::__construct(
            sprintf('Could not find "%s" binary on your system.', $binaryName)
        );
    }
}
