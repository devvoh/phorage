<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class ItemDoesNotContainKey extends Exception
{
    public static function create(string $key): self
    {
        return new self(sprintf(
            'Item does not contain key %s',
            $key,
        ));
    }
}
