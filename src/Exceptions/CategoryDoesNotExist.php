<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class CategoryDoesNotExist extends Exception
{
    public static function create(string $name): self
    {
        return new self(sprintf(
            'Category does not exist: %s',
            $name,
        ));
    }
}
