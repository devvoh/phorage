<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;

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
            $values[] = $this->makeValuesSortable($key, $items);
            $values[] = $sortType;
        }

        $values[] = SORT_NATURAL;

        return $values;
    }

    private function makeValuesSortable(string $key, array $items): array
    {
        // uuid v7 can be sorted by time, but needs to be formatted to a timestamp with microtime
        return array_map(
            function ($value) {
                try {
                    $uuid = Uuid::fromString((string)$value);

                    if ($uuid->getFields()->getVersion() === 7) {
                        return $uuid->getDateTime()->format('U.u');
                    }
                } catch (InvalidUuidStringException) {
                }

                return $value;
            },
            array_column($items, $key),
        );
    }
}
