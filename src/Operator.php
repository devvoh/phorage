<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

use Devvoh\Phorage\Operators\DataOperator;

readonly class Operator
{
    public function __construct(
        private DataOperator $dataOperator
    ) {
    }

    /**
     * @return string[]
     */
    public function listCategories(): array
    {
        return $this->dataOperator->list();
    }

    public function doesCategoryExist(string $name): bool
    {
        return $this->dataOperator->read($name) !== null;
    }

    public function createCategory(string $name): Category
    {
        $this->dataOperator->write($name, []);

        return new Category($this, $name);
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
}
