<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Operators;

interface DataOperator
{
    /**
     * @param mixed[] $data
     */
    public function write(string $key, array $data): void;

    /**
     * @return mixed[]|null
     */
    public function read(string $key): ?array;
}
