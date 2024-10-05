<?php

declare(strict_types=1);

namespace Devvoh\Phorage\Tests;

use Devvoh\Phorage\Category;
use Devvoh\Phorage\Conditions\Comparator;
use Devvoh\Phorage\Conditions\Condition;
use Devvoh\Phorage\Conditions\ConditionSet;
use Devvoh\Phorage\Exceptions\CannotGetItemFromCategory;
use Devvoh\Phorage\Exceptions\CategoryDoesNotExist;
use Devvoh\Phorage\Exceptions\CategoryNameInvalid;
use Devvoh\Phorage\Exceptions\GetByMustReturnOneItem;
use Devvoh\Phorage\Operators\InMemoryOperator;
use Devvoh\Phorage\Phorage;
use Devvoh\Phorage\Filter;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    private Phorage $db;
    private Category $category;

    public function setUp(): void
    {
        $this->db = new Phorage(new InMemoryOperator([
            'users' => [
                '01905073-67b7-73cc-9787-4c8d98ef219b' => [
                    'id' => '01905073-67b7-73cc-9787-4c8d98ef219b',
                    'name' => 'user 1',
                    'email' => 'user1@company.ext',
                    'active' => true,
                ],
                '01905073-7a46-7032-a928-1c8ec913213a' => [
                    'id' => '01905073-7a46-7032-a928-1c8ec913213a',
                    'name' => 'user 2',
                    'email' => 'user2@company.ext',
                    'active' => true,
                ],
                '01905073-7e77-7039-a7d3-f6324cf98ac9' => [
                    'id' => '01905073-7e77-7039-a7d3-f6324cf98ac9',
                    'name' => 'user 3',
                    'email' => 'user3@company.ext',
                    'deleted_at' => '2024-02-02 12:34:56', // fun little detail
                    'active' => false,
                ],
            ]
        ]));

        $this->category = $this->db->getCategory('users');
    }

    public function testCategoryList(): void
    {
        self::assertEquals(
            [
                'user 1',
                'user 2',
                'user 3',
            ],
            $this->itemsToValue($this->category->list(), 'name'),
        );
    }

    public function testCategoryListWithLimit(): void
    {
        self::assertEquals(
            [
                'user 1',
                'user 2',
            ],
            $this->itemsToValue($this->category->list(new Filter(2)), 'name'),
        );
    }

    public function testCategoryListWithOffset(): void
    {
        self::assertEquals(
            [
                'user 2',
                'user 3',
            ],
            $this->itemsToValue($this->category->list(new Filter(null, 1)), 'name'),
        );
    }

    public function testCategoryListWithOffsetAndLimit(): void
    {
        self::assertEquals(
            [
                'user 2',
            ],
            $this->itemsToValue($this->category->list(new Filter(1, 1)), 'name'),
        );
    }

    public function testCategoryListWithOrderByNameDesc(): void
    {
        $order = ['name' => SORT_DESC];

        self::assertEquals(
            [
                'user 3',
                'user 2',
                'user 1',
            ],
            $this->itemsToValue($this->category->list(new Filter(null, null, $order)), 'name'),
        );
    }

    public function testCategoryListWithOrderByNameDescAndLimit(): void
    {
        $order = ['name' => SORT_DESC];

        self::assertEquals(
            [
                'user 3',
            ],
            $this->itemsToValue($this->category->list(new Filter(1, null, $order)), 'name'),
        );
    }

    public function testCategoryListWithOrderByNameDescAndLimitAndOffset(): void
    {
        $order = ['name' => SORT_DESC];

        self::assertEquals(
            [
                'user 1', // first after skipping 2 (3 and 2 due to sorting)
            ],
            $this->itemsToValue($this->category->list(new Filter(1, 2, $order)), 'name'),
        );
    }

    public function testCategoryListWithOrderByNameAsc(): void
    {
        $order = ['name' => SORT_ASC];

        self::assertEquals(
            [
                'user 1',
                'user 2',
                'user 3',
            ],
            $this->itemsToValue($this->category->list(new Filter(null, null, $order)), 'name'),
        );
    }

    public function testCategoryListWithOrderByNameAscSortsNaturally(): void
    {
        $this->category->create([
            'name' => 'user 10',
            'email' => 'user10@company.ext',
        ]);

        $order = ['name' => SORT_ASC];

        self::assertSame(
            [
                'user 1',
                'user 2',
                'user 3',
                'user 10', // without natural sort this would end up between 1 & 2
            ],
            $this->itemsToValue($this->category->list(new Filter(null, null, $order)), 'name'),
        );
    }

    public function testCategoryListWithMultipleOrder(): void
    {
        $order = ['deleted_at' => SORT_ASC, 'name' => SORT_DESC];

        self::assertEquals(
            [
                'user 2', // goes first because we sort on name DESC
                'user 1',
                'user 3', // has deleted at, sorted ASC goes from no to yes, then value-based
            ],
            $this->itemsToValue($this->category->list(new Filter(null, null, $order)), 'name'),
        );
    }

    public function testCategoryCount(): void
    {
        self::assertSame(3, $this->category->count());
    }

    public function testGetByIdTwoFields(): void
    {
        $user = $this->category->get('01905073-7a46-7032-a928-1c8ec913213a');

        self::assertSame('user 2', $user['name']);
        self::assertSame('user2@company.ext', $user['email']);
        self::assertArrayNotHasKey('deleted_at', $user);
    }

    public function testGetByIdThreeFields(): void
    {
        $user = $this->category->get('01905073-7e77-7039-a7d3-f6324cf98ac9');

        self::assertSame('user 3', $user['name']);
        self::assertSame('user3@company.ext', $user['email']);
        self::assertSame('2024-02-02 12:34:56', $user['deleted_at']);
    }

    public function testGetByWithConditionsReturnsTheCorrectItem(): void
    {
        $user = $this->category->getBy(
            new ConditionSet(
                new Condition('id', Comparator::equals, '01905073-7e77-7039-a7d3-f6324cf98ac9')
            )
        );

        self::assertSame('user 3', $user['name']);
        self::assertSame('user3@company.ext', $user['email']);
        self::assertSame('2024-02-02 12:34:56', $user['deleted_at']);
    }

    public function testGetByWithLooserConditionsReturnsTheCorrectItem(): void
    {
        $user = $this->category->getBy(
            new ConditionSet(
                new Condition('active', Comparator::equals, false),
            )
        );

        self::assertSame('user 3', $user['name']);
        self::assertSame('user3@company.ext', $user['email']);
        self::assertSame('2024-02-02 12:34:56', $user['deleted_at']);
        self::assertFalse($user['active']);
    }

    public function testGetByWithLooserConditionsThrowsIfNotUnique(): void
    {
        $this->expectException(GetByMustReturnOneItem::class);

        $this->category->getBy(
            new ConditionSet(
                new Condition('active', Comparator::equals, true),
            )
        );
    }

    public function testCreate(): void
    {
        $user = $this->category->create([
            'name' => 'user 4',
            'email' => 'user4@company.ext',
            'deleted_at' => '2024-02-02 12:34:56',
        ]);

        self::assertSame('user 4', $user['name']);
        self::assertSame('user4@company.ext', $user['email']);
        self::assertSame('2024-02-02 12:34:56', $user['deleted_at']);

        self::assertSame(4, $this->category->count());
    }

    public function testDelete(): void
    {
        $this->category->delete('01905073-7a46-7032-a928-1c8ec913213a');

        self::assertEquals(
            [
                'user 1',
                'user 3',
            ],
            $this->itemsToValue($this->category->list(), 'name'),
        );
    }

    public function testDeleteBy(): void
    {
        $somethingDeleted = $this->category->deleteBy(new ConditionSet(
            new Condition('name', Comparator::greater_than_or_eq, 'user 2'), // yes, this works
        ));

        self::assertTrue($somethingDeleted);

        $users = $this->category->list();

        self::assertCount(1, $users);
        self::assertArrayHasKey('01905073-67b7-73cc-9787-4c8d98ef219b', $users);
        self::assertSame('user 1', $users['01905073-67b7-73cc-9787-4c8d98ef219b']['name']);
    }

    public function testUpdate(): void
    {
        $updatedUser = $this->category->update('01905073-67b7-73cc-9787-4c8d98ef219b', ['name' => 'name 1']);
        $storedUser = $this->category->get('01905073-67b7-73cc-9787-4c8d98ef219b');

        self::assertSame($updatedUser, $storedUser);
        self::assertSame('name 1', $updatedUser['name']);
    }

    public function testUpdateBy(): void
    {
        $updatedUsers = $this->category->updateBy(new ConditionSet(
            new Condition('deleted_at', Comparator::is_null),
        ), ['deleted_at' => 'now']);

        self::assertCount(2, $updatedUsers);

        self::assertSame(
            [
                '01905073-67b7-73cc-9787-4c8d98ef219b',
                '01905073-7a46-7032-a928-1c8ec913213a',
            ],
            array_keys($updatedUsers),
        );

        // and one last verify to see if all 3 now have deleted_at set
        self::assertCount(0, $this->category->listBy(new ConditionSet(new Condition('deleted_at', Comparator::is_null))));
        self::assertCount(3, $this->category->listBy(new ConditionSet(new Condition('deleted_at', Comparator::is_not_null))));
    }

    public function testUpdateWithInvalidId(): void
    {
        $this->expectException(CannotGetItemFromCategory::class);

        $this->category->update('asdasd', []);
    }

    public function testGetByConditionSetContainsStrict(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::contains_strict, 'user'),
        ));

        self::assertCount(3, $users);
    }

    public function testGetByConditionSetContainsStrictFailsOnCase(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::contains_strict, 'User'),
        ));

        self::assertCount(0, $users);
    }

    public function testGetByConditionSetContainsLoose(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::contains_loose, 'user'),
        ));

        self::assertCount(3, $users);
    }

    public function testGetByConditionSetContainsStrictDoesNotFailOnCase(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::contains_loose, 'User'),
        ));

        self::assertCount(3, $users);
    }

    public function testGetByConditionSetEquals(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::equals, 'user 1'),
        ));

        self::assertCount(1, $users);
        self::assertSame(
            [
                '01905073-67b7-73cc-9787-4c8d98ef219b' => [
                    'id' => '01905073-67b7-73cc-9787-4c8d98ef219b',
                    'name' => 'user 1',
                    'email' => 'user1@company.ext',
                    'active' => true,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetNotEquals(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::not_equals, 'user 1'),
        ));

        self::assertCount(2, $users);
        self::assertSame(
            [
                '01905073-7a46-7032-a928-1c8ec913213a' => [
                    'id' => '01905073-7a46-7032-a928-1c8ec913213a',
                    'name' => 'user 2',
                    'email' => 'user2@company.ext',
                    'active' => true,
                ],
                '01905073-7e77-7039-a7d3-f6324cf98ac9' => [
                    'id' => '01905073-7e77-7039-a7d3-f6324cf98ac9',
                    'name' => 'user 3',
                    'email' => 'user3@company.ext',
                    'deleted_at' => '2024-02-02 12:34:56',
                    'active' => false,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetIsNull(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('deleted_at', Comparator::is_null),
        ));

        self::assertCount(2, $users);
        self::assertSame(
            [
                '01905073-67b7-73cc-9787-4c8d98ef219b' => [
                    'id' => '01905073-67b7-73cc-9787-4c8d98ef219b',
                    'name' => 'user 1',
                    'email' => 'user1@company.ext',
                    'active' => true,
                ],
                '01905073-7a46-7032-a928-1c8ec913213a' => [
                    'id' => '01905073-7a46-7032-a928-1c8ec913213a',
                    'name' => 'user 2',
                    'email' => 'user2@company.ext',
                    'active' => true,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetIsNotNull(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('deleted_at', Comparator::is_not_null),
        ));

        self::assertCount(1, $users);
        self::assertSame(
            [
                '01905073-7e77-7039-a7d3-f6324cf98ac9' => [
                    'id' => '01905073-7e77-7039-a7d3-f6324cf98ac9',
                    'name' => 'user 3',
                    'email' => 'user3@company.ext',
                    'deleted_at' => '2024-02-02 12:34:56', // fun little detail
                    'active' => false,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetIsLessThan(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::less_than, 'user 3'), // yes, this works
        ));

        self::assertCount(2, $users);
        self::assertSame(
            [
                '01905073-67b7-73cc-9787-4c8d98ef219b' => [
                    'id' => '01905073-67b7-73cc-9787-4c8d98ef219b',
                    'name' => 'user 1',
                    'email' => 'user1@company.ext',
                    'active' => true,
                ],
                '01905073-7a46-7032-a928-1c8ec913213a' => [
                    'id' => '01905073-7a46-7032-a928-1c8ec913213a',
                    'name' => 'user 2',
                    'email' => 'user2@company.ext',
                    'active' => true,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetIsLessThanOrEqual(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::less_than_or_eq, 'user 2'), // yes, this works
        ));

        self::assertCount(2, $users);
        self::assertSame(
            [
                '01905073-67b7-73cc-9787-4c8d98ef219b' => [
                    'id' => '01905073-67b7-73cc-9787-4c8d98ef219b',
                    'name' => 'user 1',
                    'email' => 'user1@company.ext',
                    'active' => true,
                ],
                '01905073-7a46-7032-a928-1c8ec913213a' => [
                    'id' => '01905073-7a46-7032-a928-1c8ec913213a',
                    'name' => 'user 2',
                    'email' => 'user2@company.ext',
                    'active' => true,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetIsGreaterThan(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::greater_than, 'user 1'), // yes, this works
        ));

        self::assertCount(2, $users);
        self::assertSame(
            [
                '01905073-7a46-7032-a928-1c8ec913213a' => [
                    'id' => '01905073-7a46-7032-a928-1c8ec913213a',
                    'name' => 'user 2',
                    'email' => 'user2@company.ext',
                    'active' => true,
                ],
                '01905073-7e77-7039-a7d3-f6324cf98ac9' => [
                    'id' => '01905073-7e77-7039-a7d3-f6324cf98ac9',
                    'name' => 'user 3',
                    'email' => 'user3@company.ext',
                    'deleted_at' => '2024-02-02 12:34:56', // fun little detail
                    'active' => false,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetIsGreaterThanOrEqual(): void
    {
        $users = $this->category->listBy(new ConditionSet(
            new Condition('name', Comparator::greater_than_or_eq, 'user 2'), // yes, this works
        ));

        self::assertCount(2, $users);
        self::assertSame(
            [
                '01905073-7a46-7032-a928-1c8ec913213a' => [
                    'id' => '01905073-7a46-7032-a928-1c8ec913213a',
                    'name' => 'user 2',
                    'email' => 'user2@company.ext',
                    'active' => true,
                ],
                '01905073-7e77-7039-a7d3-f6324cf98ac9' => [
                    'id' => '01905073-7e77-7039-a7d3-f6324cf98ac9',
                    'name' => 'user 3',
                    'email' => 'user3@company.ext',
                    'deleted_at' => '2024-02-02 12:34:56', // fun little detail
                    'active' => false,
                ],
            ],
            $users
        );
    }

    public function testGetByConditionSetRespectsFilter(): void
    {
        $users = $this->category->listBy(
            new ConditionSet(
                new Condition('id', Comparator::greater_than_or_eq, '01905073-7a46-7032-a928-1c8ec913213a'),
            ),
            new Filter(order: ['id' => SORT_DESC]),
        );

        self::assertCount(2, $users);

        $userValues = array_values($users);

        self::assertSame('user 3', $userValues[0]['name']);
        self::assertSame('user 2', $userValues[1]['name']);

        $users = $this->category->listBy(
            new ConditionSet(
                new Condition('id', Comparator::less_than_or_eq, '01905073-7a46-7032-a928-1c8ec913213a'),
            ),
            new Filter(order: ['id' => SORT_ASC]),
        );

        self::assertCount(2, $users);

        $userValues = array_values($users);

        self::assertSame('user 1', $userValues[0]['name']);
        self::assertSame('user 2', $userValues[1]['name']);
    }

    public function testUuidsAreProperlySorted(): void
    {
        $category = $this->db->createCategory('test');

        $range = range(1, 50);

        foreach ($range as $count) {
            $category->create(["count" => $count]);
            usleep(2000);
        }

        $itemsAscending = $category->list(new Filter(order: ['id' => SORT_ASC]));

        foreach ($range as $count) {
            $item = array_shift($itemsAscending); // ASC means we go up, so shift off the beginning
            self::assertSame($count, $item['count']);
        }

        $itemsDescending = $category->list(new Filter(order: ['id' => SORT_DESC]));

        foreach ($range as $count) {
            $item = array_pop($itemsDescending); // ASC means we go up, so pop off the end
            self::assertSame($count, $item['count']);
        }
    }

    public function testUuidsAreProperlySortedAndFilteredAscending(): void
    {
        $category = $this->db->createCategory('test');

        $range = range(1, 20);

        foreach ($range as $count) {
            $category->create(["count" => $count]);
            usleep(2000);
        }

        $items12and13 = $category->list(
            new Filter(
                limit: 2,
                offset: 11,
                order: ['id' => SORT_ASC]
            )
        );

        $itemValues = array_values($items12and13);

        self::assertSame(12, $itemValues[0]['count']);
        self::assertSame(13, $itemValues[1]['count']);
    }

    public function testUuidsAreProperlySortedAndFilteredDescending(): void
    {
        $category = $this->db->createCategory('test');

        $range = range(1, 20);

        foreach ($range as $count) {
            $category->create(["count" => $count]);
            usleep(2000);
        }

        $items9and8 = $category->list(
            new Filter(
                limit: 2,
                offset: 11,
                order: ['id' => SORT_DESC]
            )
        );

        $itemValues = array_values($items9and8);

        self::assertSame(9, $itemValues[0]['count']);
        self::assertSame(8, $itemValues[1]['count']);
    }

    public function testUuidsAreProperlySortedWithConditionOnIdAscending(): void
    {
        $category = $this->db->createCategory('test');

        $range = range(1, 20);

        $itemId = null;

        foreach ($range as $count) {
            $itemCreated = $category->create(["count" => $count]);
            usleep(2000);

            if ($count === 13) {
                $itemId = $itemCreated['id'];
            }
        }

        self::assertNotNull($itemId);

        $items14And15 = $category->listBy(
            new ConditionSet(
                new Condition('id', Comparator::greater_than, $itemId),
            ),
            new Filter(
                limit: 2,
                order: ['id' => SORT_ASC]
            )
        );

        $itemValues = array_values($items14And15);

        self::assertSame(14, $itemValues[0]['count']);
        self::assertSame(15, $itemValues[1]['count']);
    }

    public function testUuidsAreProperlySortedWithConditionOnIdDescending(): void
    {
        $category = $this->db->createCategory('test');

        $range = range(1, 20);

        $itemId = null;

        foreach ($range as $count) {
            $itemCreated = $category->create(["count" => $count]);
            usleep(2000);

            if ($count === 13) {
                $itemId = $itemCreated['id'];
            }
        }

        self::assertNotNull($itemId);

        $items12And11 = $category->listBy(
            new ConditionSet(
                new Condition('id', Comparator::less_than, $itemId),
            ),
            new Filter(
                limit: 2,
                order: ['id' => SORT_DESC]
            )
        );

        $itemValues = array_values($items12And11);

        self::assertSame(12, $itemValues[0]['count']);
        self::assertSame(11, $itemValues[1]['count']);
    }

    /**
     * @throws CategoryDoesNotExist
     * @throws CategoryNameInvalid
     */
    public function testListByConditionSetWithMultipleConditions(): void
    {
        // we need better data to really test this
        $db = new Phorage(new InMemoryOperator([
            'stuff' => [
                'id-1' => [
                    'id' => 'id-1',
                    'value' => 6,
                    'visible' => true,
                ],
                'id-2' => [
                    'id' => 'id-2',
                    'value' => 5,
                    'visible' => false,
                ],
                'id-3' => [
                    'id' => 'id-3',
                    'value' => 4,
                    'visible' => true,
                ],
                'id-4' => [
                    'id' => 'id-4',
                    'value' => 3,
                    'visible' => false,
                ],
                'id-5' => [
                    'id' => 'id-5',
                    'value' => 2,
                    'visible' => true,
                ],
                'id-6' => [
                    'id' => 'id-6',
                    'value' => 1,
                    'visible' => false,
                ],
            ],
        ]));

        $category = $db->getCategory('stuff');

        // set up conditions
        $condition1 = new Condition('visible', Comparator::equals, true);
        $condition2 = new Condition('id', Comparator::greater_than_or_eq, 'id-2');
        $condition3 = new Condition('value', Comparator::less_than, 5);

        // now first we try all conditions individually

        $itemsVisible = $category->listBy(new ConditionSet($condition1));
        $itemsWithId2OrHigher = $category->listBy(new ConditionSet($condition2));
        $itemsWithValue5OrLower = $category->listBy(new ConditionSet($condition3));

        self::assertCount(3, $itemsVisible);
        self::assertSame(
            ['id-1', 'id-3', 'id-5'],
            array_keys($itemsVisible),
        );

        self::assertCount(5, $itemsWithId2OrHigher); // only item 1 excluded
        self::assertSame(
            ['id-2', 'id-3', 'id-4', 'id-5', 'id-6'],
            array_keys($itemsWithId2OrHigher),
        );

        self::assertCount(4, $itemsWithValue5OrLower); // only items 1 and 2 excluded
        self::assertSame(
            ['id-3', 'id-4', 'id-5', 'id-6'],
            array_keys($itemsWithValue5OrLower),
        );

        $items = $category->listBy(new ConditionSet($condition1, $condition2, $condition3)); // only items 3 and 5

        /*
         * item 3   is visible    has id higher than 2      has value lower than 5 (4)
         * item 5   is visible    has id higher than 2      has value lower than 5 (2)
         */
        self::assertCount(2, $items);
        self::assertSame(
            ['id-3', 'id-5'],
            array_keys($items),
        );
    }

    private function itemsToValue(array $items, string $key): array
    {
        $mapped = array_map(
            fn(array $item): string => $item[$key],
            $items
        );

        return array_values($mapped);
    }
}
