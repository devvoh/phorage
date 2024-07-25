<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Tests;

use Devvoh\Phorage\Category;
use Devvoh\Phorage\Exceptions\CategoryAlreadyExists;
use Devvoh\Phorage\Exceptions\CategoryDoesNotExist;
use Devvoh\Phorage\Exceptions\CategoryNameInvalid;
use Devvoh\Phorage\Operators\InMemoryOperator;
use Devvoh\Phorage\Phorage;
use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    private Phorage $db;

    public function setUp(): void
    {
        $this->db = new Phorage(new InMemoryOperator([]));
    }

    public function testListCategoriesReturnsEmptyArray(): void
    {
        self::assertSame([], $this->db->listCategories());
    }

    public function testListCategoriesReturnsArrayOfCategories(): void
    {
        $this->db->createCategory('test');
        $this->db->createCategory('test2');

        $categories = $this->db->listCategories();

        self::assertCount(2, $categories);

        self::assertSame('test', $categories[0]->name);
        self::assertSame('test2', $categories[1]->name);
    }

    public function testGetCategoryIfCategoryDoesNotExist(): void
    {
        $this->expectException(CategoryDoesNotExist::class);

        $this->db->getCategory('test');
    }

    public function testCreateCategory(): void
    {
        $category = $this->db->createCategory('test');

        self::assertInstanceOf(Category::class, $category);
        self::assertSame([], $category->list());
    }

    public function testCreateCategoryIfCategoryAlreadyExists(): void
    {
        $this->db->createCategory('test');

        $this->expectException(CategoryAlreadyExists::class);

        $this->db->createCategory('test');
    }

    public function testGetCategoryIfNotExists(): void
    {
        $this->expectException(CategoryDoesNotExist::class);

        self::assertInstanceOf(Category::class, $this->db->getCategory('test'));
    }

    public function testGetCategory(): void
    {
        $this->db->createCategory('test');

        self::assertInstanceOf(Category::class, $this->db->getCategory('test'));
    }

    /**
     * @dataProvider dpCategoryNames
     */
    public function testNameValidationWorksForCreate(string $name, bool $valid): void
    {
        if ($valid) {
            self::expectNotToPerformAssertions();
        } else {
            $this->expectException(CategoryNameInvalid::class);
        }

        $this->db->createCategory($name);
    }

    /**
     * @dataProvider dpCategoryNames
     */
    public function testNameValidationWorksForGet(string $name, bool $valid): void
    {
        if ($valid) {
            self::expectNotToPerformAssertions();
        } else {
            $this->expectException(CategoryNameInvalid::class);
        }

        $this->db->createCategory($name);
    }

    public function dpCategoryNames(): array
    {
        return [
            [
                'name' => 'good',
                'valid' => true,
            ],
            [
                'name' => 'good123',
                'valid' => true,
            ],
            [
                'name' => 'good_123',
                'valid' => true,
            ],
            [
                'name' => 'bad?',
                'valid' => false,
            ],
            [
                'name' => '/home/user',
                'valid' => false,
            ],
            [
                'name' => 'test-stuff',
                'valid' => false,
            ],
            [
                'name' => '../../../',
                'valid' => false,
            ],
        ];
    }
}
