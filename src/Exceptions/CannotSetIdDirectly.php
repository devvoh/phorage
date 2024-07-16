<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class CannotSetIdDirectly extends Exception
{
    public static function create(): self
    {
        return new self('Id cannot be set directly.');
    }
}
