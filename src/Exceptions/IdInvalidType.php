<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class IdInvalidType extends Exception
{
    public static function create(mixed $id): self
    {
        return new self(sprintf(
            'Id is of invalid type: %s instead of uuid string',
            gettype($id),
        ));
    }
}
