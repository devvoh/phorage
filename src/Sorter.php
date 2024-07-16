<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

class Sorter
{
    /**
     * @param mixed[][] $items
     */
    public function __construct(private array $items)
    {
    }

    /**
     * @return mixed[][]
     */
    public function sortByFilter(?Filter $filter): array
    {
        if ($filter === null || !$filter->order) {
            return $this->items;
        }

        $multisortValues = $this->breakFilterIntoMultiSort($this->items, $filter);

        $multisortValues[] = &$this->items;

        call_user_func_array('array_multisort', $multisortValues);

        foreach ($this->items as $id => $item) {
            $this->items[$id] = array_filter($item);
        }

        return $this->items;
    }

    /**
     * @param mixed[][] $items
     * @return mixed[][]
     */
    private function breakFilterIntoMultiSort(array $items, Filter $filter): array
    {
        $values = [];

        foreach ($items as $id => $item) {
            foreach ($filter->order ?? [] as $key => $sortType) {
                if (!isset($item[$key])) {
                    $items[$id][$key] = null;
                }
            }
        }

        foreach ($filter->order ?? [] as $key => $sortType) {
            $values[] = array_column($items, $key);
            $values[] = $sortType;
        }

        $values[] = SORT_NATURAL;

        return $values;
    }
}
