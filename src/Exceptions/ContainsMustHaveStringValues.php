<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Exception;

class ContainsMustHaveStringValues extends Exception
{
    public static function create(mixed $itemValue, mixed $conditionValue): self
    {
        return new self(sprintf(
            "Comparator::contains_strict and loose can only be used on string values, was told to look for '%s' in '%s'",
            var_export($conditionValue, true),
            var_export($itemValue, true),
        ));
    }
}
