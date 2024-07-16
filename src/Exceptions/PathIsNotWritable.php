<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class PathIsNotWritable extends Exception
{
    public static function create(string $path): self
    {
        return new self(sprintf(
            'Path is not writable: %s',
            $path,
        ));
    }
}
