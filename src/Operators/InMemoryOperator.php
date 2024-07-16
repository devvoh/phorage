<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Operators;

class InMemoryOperator implements DataOperator
{
    /**
     * @param mixed[] $data
     */
    public function __construct(
        private array $data
    ) {
    }

    /**
     * @inheritDoc
     */
    public function read(string $key): ?array
    {
        return $this->data[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function write(string $key, array $data): void
    {
        $this->data[$key] = $data;
    }
}
