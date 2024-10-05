<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Conditions;

use Devvoh\Phorage\Exceptions\ContainsMustHaveStringValues;
use Devvoh\Phorage\Exceptions\ItemDoesNotContainKey;

class ConditionSet
{
    /** @var Condition[] */
    private array $conditions;

    public function __construct(
        Condition ...$conditions
    ) {
        $this->conditions = $conditions;
    }

    /**
     * @param mixed[][] $items
     * @return mixed[][]
     */
    public function match(array $items): array
    {
        $matches = [];

        foreach ($items as $item) {
            if ($this->matchItem($item)) {
                $matches[$item['id']] = $item;
            }
        }

        return $matches;
    }

    /**
     * @param mixed[] $item
     */
    public function matchItem(array $item): bool
    {
        $conditionMatchesNeeded = count($this->conditions);
        $conditionsMatched = 0;

        foreach ($this->conditions as $condition) {
            if (!array_key_exists($condition->key, $item) && $condition->comparator !== Comparator::is_null) {
                continue;
            }

            $conditionsMatched += match($condition->comparator) {
                Comparator::contains_strict => self::matchContainsStrict($item, $condition),
                Comparator::contains_loose => self::matchContainsLoose($item, $condition),
                Comparator::equals => self::matchEquals($item, $condition),
                Comparator::not_equals => self::matchNotEquals($item, $condition),
                Comparator::is_null => self::matchIsNull($item, $condition),
                Comparator::is_not_null => self::matchIsNotNull($item, $condition),
                Comparator::less_than => self::matchIsLessThan($item, $condition),
                Comparator::less_than_or_eq => self::matchIsLessThanOrEqual($item, $condition),
                Comparator::greater_than => self::matchIsGreaterThan($item, $condition),
                Comparator::greater_than_or_eq => self::matchIsGreaterThanOrEqual($item, $condition),
            };
        }

        return $conditionsMatched === $conditionMatchesNeeded;
    }

    /**
     * @param mixed[] $item
     * @throws ContainsMustHaveStringValues
     */
    private function matchContainsStrict(array $item, Condition $condition): int
    {
        if (!is_string($item[$condition->key]) || !is_string($condition->value)) {
            throw ContainsMustHaveStringValues::create($item[$condition->key], $condition->value);
        }

        return str_contains((string)$item[$condition->key], (string)$condition->value) ? 1 : 0;
    }

    /**
     * @param mixed[] $item
     * @throws ContainsMustHaveStringValues
     */
    private function matchContainsLoose(array $item, Condition $condition): int
    {
        if (!is_string($item[$condition->key]) || !is_string($condition->value)) {
            throw ContainsMustHaveStringValues::create($item[$condition->key], $condition->value);
        }

        return str_contains(
            strtoupper((string)$item[$condition->key]),
            strtoupper((string)$condition->value),
        ) ? 1 : 0;
    }

    /**
     * @param mixed[] $item
     */
    private function matchEquals(array $item, Condition $condition): int
    {
        return ($item[$condition->key] ?? null) === $condition->value ? 1 : 0;
    }

    /**
     * @param mixed[] $item
     */
    private function matchNotEquals(array $item, Condition $condition): int
    {
        return $this->reverse($this->matchEquals($item, $condition));
    }

    /**
     * @param mixed[] $item
     */
    private function matchIsNull(array $item, Condition $condition): int
    {
        return isset($item[$condition->key]) ? 0 : 1;
    }

    /**
     * @param mixed[] $item
     */
    private function matchIsNotNull(array $item, Condition $condition): int
    {
        return $this->reverse($this->matchIsNull($item, $condition));
    }

    /**
     * @param mixed[] $item
     */
    private function matchIsLessThan(array $item, Condition $condition): int
    {
        return ($item[$condition->key] ?? null) < $condition->value ? 1 : 0;
    }

    /**
     * @param mixed[] $item
     */
    private function matchIsLessThanOrEqual(array $item, Condition $condition): int
    {
        return ($item[$condition->key] ?? null) <= $condition->value ? 1 : 0;
    }

    /**
     * @param mixed[] $item
     */
    private function matchIsGreaterThan(array $item, Condition $condition): int
    {
        return ($item[$condition->key] ?? null) > $condition->value ? 1 : 0;
    }

    /**
     * @param mixed[] $item
     */
    private function matchIsGreaterThanOrEqual(array $item, Condition $condition): int
    {
        return ($item[$condition->key] ?? null) >= $condition->value ? 1 : 0;
    }

    private function reverse(int $value): int
    {
        return $value === 1 ? 0 : 1;
    }
}
