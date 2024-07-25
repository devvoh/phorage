<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

use Devvoh\Phorage\Exceptions\CategoryAlreadyExists;
use Devvoh\Phorage\Exceptions\CategoryDoesNotExist;
use Devvoh\Phorage\Exceptions\CategoryNameInvalid;
use Devvoh\Phorage\Operators\DataOperator;

readonly class Phorage
{
    public function __construct(
        private DataOperator $dataOperator
    ) {
    }

    /**
     * @throws CategoryAlreadyExists|CategoryNameInvalid
     */
    public function createCategory(string $name): Category
    {
        $this->validateCategoryName($name);

        if ($this->doesCategoryExist($name)) {
            throw CategoryAlreadyExists::create($name);
        }

        $this->dataOperator->write($name, []);

        return new Category($this, $name);
    }

    /**
     * @throws CategoryDoesNotExist|CategoryNameInvalid
     */
    public function getCategory(string $name): ?Category
    {
        $this->validateCategoryName($name);

        if (!$this->doesCategoryExist($name)) {
            throw CategoryDoesNotExist::create($name);
        }

        return new Category($this, $name);
    }

    /**
     * @return Category[]
     * @throws CategoryDoesNotExist|CategoryNameInvalid
     */
    public function listCategories(): array
    {
        $categories = [];

        foreach ($this->dataOperator->list() as $categoryName) {
            $categories[] = $this->getCategory($categoryName);
        }

        return $categories;
    }

    public function doesCategoryExist(string $name): bool
    {
        return $this->dataOperator->read($name) !== null;
    }

    /**
     * @return mixed[][]
     */
    public function loadCategoryContent(string $name): array
    {
        return $this->dataOperator->read($name);
    }

    /**
     * @param mixed[][] $data
     */
    public function saveCategoryContent(string $name, array $data): void
    {
        $this->dataOperator->write($name, $data);
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
