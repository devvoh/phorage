<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

use Devvoh\Phorage\Exceptions\CategoryAlreadyExists;
use Devvoh\Phorage\Exceptions\CategoryDoesNotExist;
use Devvoh\Phorage\Exceptions\CategoryNameInvalid;

readonly class Phorage
{
    public function __construct(
        private Operator $operator
    ) {
    }

    /**
     * @throws CategoryAlreadyExists|CategoryNameInvalid
     */
    public function createCategory(string $name): Category
    {
        $this->validateCategoryName($name);

        if ($this->operator->doesCategoryExist($name)) {
            throw CategoryAlreadyExists::create($name);
        }

        return $this->operator->createCategory($name);
    }

    /**
     * @throws CategoryDoesNotExist|CategoryNameInvalid
     */
    public function getCategory(string $name): ?Category
    {
        $this->validateCategoryName($name);

        if (!$this->operator->doesCategoryExist($name)) {
            throw CategoryDoesNotExist::create($name);
        }

        return new Category($this->operator, $name);
    }

    /**
     * @return Category[]
     * @throws CategoryDoesNotExist|CategoryNameInvalid
     */
    public function listCategories(): array
    {
        $categories = [];

        foreach ($this->operator->listCategories() as $categoryName) {
            $categories[] = $this->getCategory($categoryName);
        }

        return $categories;
    }

    /**
     * @throws CategoryNameInvalid
     */
    private function validateCategoryName(string $name): void
    {
        preg_match_all('/^([a-z0-9_]+)/', $name, $matches);

        if (isset($matches[1][0]) && $matches[1][0] === $name) {
            return;
        }

        throw CategoryNameInvalid::create($name);
    }
}
