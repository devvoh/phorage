<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class CategoryNameInvalid extends Exception
{
    public static function create(string $name): self
    {
        return new self(sprintf(
            'Category name is invalid (a-z, 0-9 and _ allowed): %s',
            $name,
        ));
    }
}
