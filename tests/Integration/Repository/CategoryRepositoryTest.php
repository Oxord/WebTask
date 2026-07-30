<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Repository\CategoryRepository;
use Tests\Support\IntegrationTestCase;

final class CategoryRepositoryTest extends IntegrationTestCase
{
    public function testWithProductCountsOnlyCountsActiveProducts(): void
    {
        $repo = new CategoryRepository(self::$pdo);
        $category = $this->createCategory();
        $empty = $this->createCategory();

        $this->createProduct(['category_id' => $category['id'], 'is_active' => 1]);
        $this->createProduct(['category_id' => $category['id'], 'is_active' => 1]);
        $this->createProduct(['category_id' => $category['id'], 'is_active' => 0]);

        $result = $repo->withProductCounts();

        $byId = [];
        foreach ($result as $row) {
            $byId[$row['id']] = $row;
        }

        self::assertSame(2, (int) $byId[$category['id']]['product_count']);
        self::assertSame(0, (int) $byId[$empty['id']]['product_count']);
    }

    public function testWithProductCountsIncludesCategoriesWithoutProducts(): void
    {
        $repo = new CategoryRepository(self::$pdo);
        $category = $this->createCategory();

        $result = $repo->withProductCounts();

        $ids = array_column($result, 'id');
        self::assertContains($category['id'], $ids);
    }

    public function testFindAndFindBySlug(): void
    {
        $repo = new CategoryRepository(self::$pdo);
        $category = $this->createCategory(['slug' => 'unique-category-slug']);

        self::assertSame($category['id'], $repo->find((int) $category['id'])['id']);
        self::assertSame($category['id'], $repo->findBySlug('unique-category-slug')['id']);
    }

    public function testCreateUpdateDelete(): void
    {
        $repo = new CategoryRepository(self::$pdo);

        $id = $repo->create(['name' => 'Новая', 'slug' => 'novaya-cat', 'description' => 'd', 'sort_order' => 5]);
        $created = $repo->find($id);
        self::assertSame('Новая', $created['name']);

        $repo->update($id, ['name' => 'Обновлённая', 'slug' => 'novaya-cat', 'description' => 'd2', 'sort_order' => 1]);
        $updated = $repo->find($id);
        self::assertSame('Обновлённая', $updated['name']);

        self::assertTrue($repo->delete($id));
        self::assertNull($repo->find($id));
    }
}
