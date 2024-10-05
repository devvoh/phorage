<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

use Devvoh\Phorage\Conditions\ConditionSet;
use Devvoh\Phorage\Exceptions\CannotGetItemFromCategory;
use Devvoh\Phorage\Exceptions\CannotSetIdDirectly;
use Devvoh\Phorage\Exceptions\GetByMustReturnOneItem;
use Devvoh\Phorage\Exceptions\IdInvalidType;
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

        if ($filter !== null) {
            $items = $this->filterItems($items, $filter);
        }

        return $items;
    }

    /**
     * @return mixed[][]
     */
    public function listBy(ConditionSet $conditionSet, ?Filter $filter = null): array
    {
        $items = $conditionSet->match($this->list());

        if ($filter !== null) {
            $items = $this->filterItems($items, $filter);
        }

        return $items;
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
     * @return mixed[]
     * @throws GetByMustReturnOneItem
     */
    public function getBy(ConditionSet $conditionSet): ?array
    {
        $items = array_values($this->listBy($conditionSet));

        if (count($items) > 1) {
            throw GetByMustReturnOneItem::create($this);
        }

        return $items[0] ?? null;
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
     * @throws IdInvalidType
     */
    public function updateBy(ConditionSet $conditionSet, array $data): array
    {
        $toBeUpdated = $this->listBy($conditionSet);

        foreach ($toBeUpdated as $item) {
            if (is_string($item['id'])) {
                $this->update($item['id'], $data);
            } else {
                throw IdInvalidType::create($item['id']);
            }
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

    /**
     * @throws IdInvalidType
     */
    public function deleteBy(ConditionSet $conditionSet): bool
    {
        $toBeDeleted = $this->listBy($conditionSet);

        foreach ($toBeDeleted as $item) {
            if (is_string($item['id'])) {
                $this->delete($item['id']);
            } else {
                throw IdInvalidType::create($item['id']);
            }
        }

        return count($toBeDeleted) > 0;
    }

    /**
     * @param mixed[] $items
     * @return mixed[]
     */
    private function filterItems(array $items, Filter $filter): array
    {
        $sorted = (new Sorter($items))->sortByFilter($filter);
        return array_slice($sorted, $filter->offset ?? 0, $filter->limit, true);
    }
}
