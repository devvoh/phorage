<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Conditions;

class Condition
{
    public function __construct(
        public readonly string $key,
        public readonly Comparator $comparator,
        public readonly mixed $value = null,
    ) {
    }
}
