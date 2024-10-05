<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Devvoh\Phorage\Category;
use Exception;

class GetByMustReturnOneItem extends Exception
{
    public static function create(Category $category): self
    {
        return new self(sprintf(
            'Cannot get unique item from category %s with conditions provided.',
            $category->name,
        ));
    }
}
