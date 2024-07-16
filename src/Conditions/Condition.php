<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Conditions;

readonly class Condition
{
    public function __construct(
        public string $key,
        public Comparator $comparator,
        public mixed $value = null,
    ) {
    }
}
