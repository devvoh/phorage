<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Exceptions;

use Devvoh\Phorage\Category;
use Exception;

class CannotGetItemFromCategory extends Exception
{
    public static function create(Category $category, string $id): self
    {
        return new self(sprintf(
            'Cannot get item from category %s with id %s.',
            $category->name,
            $id,
        ));
    }
}
