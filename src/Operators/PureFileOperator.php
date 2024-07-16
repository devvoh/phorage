<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Operators;

use Devvoh\Phorage\Exceptions\PathDoesNotExist;
use Devvoh\Phorage\Exceptions\PathIsNotWritable;

readonly class PureFileOperator implements DataOperator
{
    /**
     * @throws PathIsNotWritable
     * @throws PathDoesNotExist
     */
    public function __construct(
        private string $path
    ) {
        $this->validatePathExists($this->path);
        $this->validatePathIsWritable($this->path);
    }

    /**
     * @inheritDoc
     */
    public function write(string $key, array $data): void
    {
        $path = "$this->path/category_$key.json";

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @inheritDoc
     */
    public function read(string $key): ?array
    {
        $path = "$this->path/category_$key.json";

        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        return json_decode($content, true);
    }

    /**
     * @inheritDoc
     */
    public function list(): array
    {
        $categories = [];

        $globbed = glob("$this->path/category*");

        if ($globbed === false) {
            return [];
        }

        foreach ($globbed as $category) {
            $parts = explode("/", $category);
            $name = end($parts);

            if (str_contains($name, '.json') && str_contains($name, 'category_')) {
                $categories[] = str_replace(['category_', '.json'], '', $name);
            }
        }

        return $categories;
    }

    /**
     * @throws PathDoesNotExist
     */
    private function validatePathExists(string $path): void
    {
        if (!file_exists($path)) {
            throw PathDoesNotExist::create($path);
        }
    }

    /**
     * @throws PathIsNotWritable
     */
    private function validatePathIsWritable(string $path): void
    {
        if (!is_writable($path)) {
            throw PathIsNotWritable::create($path);
        }
    }
}
