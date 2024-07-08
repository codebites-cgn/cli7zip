<?php

namespace Codebites\Cli7zip\Exception;

use Exception;

class InsufficientPermissionsException extends Exception
{
    public function __construct(string $fileOrFolder, string $missingPermission)
    {
        parent::__construct(
            sprintf('Insufficient permission for "%s": %s', $fileOrFolder, $missingPermission)
        );
    }
}
