<?php

declare(strict_types=1);

namespace Devvoh\Phorage;

readonly class Filter
{
    public function __construct(
        public ?int $limit = null,
        public ?int $offset = null,
        /** @var mixed[], $order */
        public ?array $order = null,
    ) {
    }
}
