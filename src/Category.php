<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

use Devvoh\Phorage\Conditions\ConditionSet;
use Devvoh\Phorage\Exceptions\CannotGetItemFromCategory;
use Devvoh\Phorage\Exceptions\CannotSetIdDirectly;
use Ramsey\Uuid\Uuid;

readonly class Category
{
    public function __construct(
        private Phorage $phorage,
        public string $name,
    ) {
    }

    /**
     * @return mixed[][]
     */
    public function list(?Filter $filter = null): array
    {
        $items = $this->phorage->loadCategoryContent($this->name);

        if ($filter) {
            $sorted = (new Sorter($items))->sortByFilter($filter);
            $items = array_slice($sorted, $filter->offset ?? 0, $filter->limit, true);
        }

        return $items;
    }

    /**
     * @return mixed[][]
     */
    public function listBy(ConditionSet $conditionSet): array
    {
        return $conditionSet->match($this->list());
    }

    public function count(): int
    {
        return count($this->list());
    }

    /**
     * @return mixed[]
     */
    public function get(string $id): ?array
    {
        return $this->list()[$id] ?? null;
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     * @throws CannotSetIdDirectly
     */
    public function create(array $data): array
    {
        if (isset($data['id'])) {
            throw CannotSetIdDirectly::create();
        }

        $data['id'] = Uuid::uuid7()->toString();

        $list = $this->list();

        $list[$data['id']] = $data;

        $this->phorage->saveCategoryContent($this->name, $list);

        return $data;
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     * @throws CannotGetItemFromCategory
     */
    public function update(string $id, array $data): array
    {
        $items = $this->list();

        if (!isset($items[$id])) {
            throw CannotGetItemFromCategory::create($this, $id);
        }

        foreach ($data as $key => $value) {
            if ($value === null) {
                unset($items[$id][$key]);
            } else {
                $items[$id][$key] = $value;
            }
        }

        $this->phorage->saveCategoryContent($this->name, $items);

        return $items[$id];
    }

    /**
     * @param mixed[] $data
     * @return mixed[]
     * @throws CannotGetItemFromCategory
     */
    public function updateBy(ConditionSet $conditionSet, array $data): array
    {
        $toBeUpdated = $this->listBy($conditionSet);

        foreach ($toBeUpdated as $item) {
            $this->update($item['id'], $data);
        }

        return $toBeUpdated;
    }

    public function delete(string $id): bool
    {
        $list = $this->list();

        if (!isset($list[$id])) {
            return false;
        }

        unset($list[$id]);

        $this->phorage->saveCategoryContent($this->name, $list);

        return true;
    }

    public function deleteBy(ConditionSet $conditionSet): bool
    {
        $toBeDeleted = $this->listBy($conditionSet);

        foreach ($toBeDeleted as $item) {
            $this->delete($item['id']);
        }

        return count($toBeDeleted) > 0;
    }
}
