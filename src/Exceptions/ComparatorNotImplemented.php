<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Devvoh\Phorage\Conditions\Comparator;
use Exception;

class ComparatorNotImplemented extends Exception
{
    public static function create(Comparator $comparator): self
    {
        return new self(sprintf(
            'Comparator %s is not implemented.',
            $comparator->name
        ));
    }
}
