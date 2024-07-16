<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class PathDoesNotExist extends Exception
{
    public static function create(string $path): self
    {
        return new self(sprintf(
            'Path does not exist: %s',
            $path,
        ));
    }
}
