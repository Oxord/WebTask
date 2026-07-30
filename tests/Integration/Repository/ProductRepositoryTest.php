<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use PDOException;
use Tests\Support\IntegrationTestCase;

/**
 * ProductRepository::search() is the most complex query in the project (dynamic WHERE,
 * a correlated discount subquery joined in, every sort mode, pagination). It had never been
 * exercised against a real MySQL instance before this suite — unit tests only mock the
 * repository. This class runs non-transactionally (see isTransactional()) because InnoDB only
 * syncs FULLTEXT indexes to disk at COMMIT, so MATCH() ... AGAINST() cannot see rows inserted
 * earlier in the same still-open transaction.
 */
final class ProductRepositoryTest extends IntegrationTestCase
{
    protected function isTransactional(): bool
    {
        return false;
    }

    public function testFindBySlugReturnsProductWithDiscountFields(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $product = $this->createProduct(['slug' => 'vaza-unique-slug', 'price' => '500.00']);

        $found = $repo->findBySlug('vaza-unique-slug');

        self::assertNotNull($found);
        self::assertSame($product['id'], $found['id']);
        self::assertSame(0, (int) $found['discount_percent']);
        self::assertEqualsWithDelta(500.00, (float) $found['final_price'], 0.001);
    }

    public function testFindBySlugReturnsNullForMissing(): void
    {
        $repo = new ProductRepository(self::$pdo);

        self::assertNull($repo->findBySlug('does-not-exist'));
    }

    public function testFeaturedReturnsOnlyFeaturedAndActive(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $featured = $this->createProduct(['is_featured' => 1, 'is_active' => 1]);
        $this->createProduct(['is_featured' => 0, 'is_active' => 1]);
        $this->createProduct(['is_featured' => 1, 'is_active' => 0]);

        $result = $repo->featured(10);

        $ids = array_column($result, 'id');
        self::assertContains($featured['id'], $ids);
        self::assertCount(1, $result);
    }

    public function testSearchByFullTextQueryFindsMatchingProduct(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $target = $this->createProduct([
            'name' => 'Керамическая ваза Прованс',
            'description' => 'Стильная ваза для цветов ручной работы',
        ]);
        $this->createProduct([
            'name' => 'Деревянная рамка',
            'description' => 'Рамка для фотографий из дуба',
        ]);

        $result = $repo->search(['q' => 'ваза']);

        $ids = array_column($result['items'], 'id');
        self::assertContains($target['id'], $ids);
        self::assertCount(1, $result['items']);
        self::assertSame(1, $result['total']);
    }

    public function testSearchByShortQueryUsesLikeFallback(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $this->createProduct(['name' => 'Ковер шерстяной', 'description' => 'Мягкий ковер']);
        $this->createProduct(['name' => 'Полка настенная', 'description' => 'Деревянная полка']);

        // "ко" короче 3 символов — search() уходит в LIKE-ветку вместо FULLTEXT.
        $result = $repo->search(['q' => 'ко']);

        $names = array_column($result['items'], 'name');
        self::assertContains('Ковер шерстяной', $names);
    }

    public function testSearchByCategoryFilter(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $categoryA = $this->createCategory();
        $categoryB = $this->createCategory();
        $inA = $this->createProduct(['category_id' => $categoryA['id']]);
        $this->createProduct(['category_id' => $categoryB['id']]);

        $result = $repo->search(['category_id' => $categoryA['id']]);

        self::assertSame(1, $result['total']);
        self::assertSame($inA['id'], $result['items'][0]['id']);
    }

    public function testSearchByPriceRange(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $this->createProduct(['price' => '100.00']);
        $mid = $this->createProduct(['price' => '500.00']);
        $this->createProduct(['price' => '900.00']);

        $result = $repo->search(['price_min' => 300, 'price_max' => 700]);

        self::assertSame(1, $result['total']);
        self::assertSame($mid['id'], $result['items'][0]['id']);
    }

    public function testSearchInStockFilter(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $inStock = $this->createProduct(['stock' => 5]);
        $this->createProduct(['stock' => 0]);

        $result = $repo->search(['in_stock' => 1]);

        self::assertSame(1, $result['total']);
        self::assertSame($inStock['id'], $result['items'][0]['id']);
    }

    public function testSearchOnSaleFilterOnlyReturnsDiscountedProducts(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $promotions = new PromotionRepository(self::$pdo);

        $onSale = $this->createProduct();
        $notOnSale = $this->createProduct();
        $promo = $this->createPromotion(['discount_percent' => 15]);
        $promotions->attachProducts((int) $promo['id'], [$onSale['id']]);

        $result = $repo->search(['on_sale' => 1]);

        $ids = array_column($result['items'], 'id');
        self::assertContains($onSale['id'], $ids);
        self::assertNotContains($notOnSale['id'], $ids);
    }

    public function testDiscountPercentAndFinalPriceWithActivePromotion(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $promotions = new PromotionRepository(self::$pdo);

        $product = $this->createProduct(['price' => '200.00']);
        $promo = $this->createPromotion(['discount_percent' => 25]);
        $promotions->attachProducts((int) $promo['id'], [$product['id']]);

        $found = $repo->findBySlug($product['slug']);

        self::assertSame(25, (int) $found['discount_percent']);
        self::assertEqualsWithDelta(150.00, (float) $found['final_price'], 0.001);
    }

    public function testExpiredPromotionDoesNotApplyDiscount(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $promotions = new PromotionRepository(self::$pdo);

        $product = $this->createProduct(['price' => '200.00']);
        $expired = $this->createPromotion([
            'discount_percent' => 40,
            'starts_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        $promotions->attachProducts((int) $expired['id'], [$product['id']]);

        $found = $repo->findBySlug($product['slug']);

        self::assertSame(0, (int) $found['discount_percent']);
        self::assertEqualsWithDelta(200.00, (float) $found['final_price'], 0.001);
    }

    public function testInactivePromotionDoesNotApplyDiscount(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $promotions = new PromotionRepository(self::$pdo);

        $product = $this->createProduct(['price' => '200.00']);
        $inactive = $this->createPromotion(['discount_percent' => 40, 'is_active' => 0]);
        $promotions->attachProducts((int) $inactive['id'], [$product['id']]);

        $found = $repo->findBySlug($product['slug']);

        self::assertSame(0, (int) $found['discount_percent']);
        self::assertEqualsWithDelta(200.00, (float) $found['final_price'], 0.001);
    }

    public function testFuturePromotionDoesNotApplyDiscount(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $promotions = new PromotionRepository(self::$pdo);

        $product = $this->createProduct(['price' => '200.00']);
        $future = $this->createPromotion([
            'discount_percent' => 40,
            'starts_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+10 days')),
        ]);
        $promotions->attachProducts((int) $future['id'], [$product['id']]);

        $found = $repo->findBySlug($product['slug']);

        self::assertSame(0, (int) $found['discount_percent']);
    }

    public function testSearchSortByPriceAscAndDesc(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $cheap = $this->createProduct(['price' => '100.00']);
        $mid = $this->createProduct(['price' => '500.00']);
        $expensive = $this->createProduct(['price' => '900.00']);

        $asc = $repo->search(['sort' => 'price_asc']);
        self::assertSame(
            [$cheap['id'], $mid['id'], $expensive['id']],
            array_column($asc['items'], 'id')
        );

        $desc = $repo->search(['sort' => 'price_desc']);
        self::assertSame(
            [$expensive['id'], $mid['id'], $cheap['id']],
            array_column($desc['items'], 'id')
        );
    }

    public function testSearchSortByName(): void
    {
        // Prefixed with digits so ordering is unambiguous regardless of collation
        // (Cyrillic byte-order in PHP and utf8mb4_unicode_ci order in MySQL can disagree).
        $repo = new ProductRepository(self::$pdo);
        $this->createProduct(['name' => '3 Яблоко декоративное']);
        $this->createProduct(['name' => '1 Ангел статуэтка']);
        $this->createProduct(['name' => '2 Ёлка новогодняя']);

        $result = $repo->search(['sort' => 'name']);

        $names = array_column($result['items'], 'name');
        self::assertSame(
            ['1 Ангел статуэтка', '2 Ёлка новогодняя', '3 Яблоко декоративное'],
            $names
        );
    }

    public function testSearchSortByNewest(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $older = $this->createProduct();
        self::$pdo->exec("UPDATE products SET created_at = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE id = {$older['id']}");
        $newer = $this->createProduct();

        $result = $repo->search(['sort' => 'newest']);

        self::assertSame($newer['id'], $result['items'][0]['id']);
    }

    public function testSearchDefaultSortIsNewestFirst(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $older = $this->createProduct();
        self::$pdo->exec("UPDATE products SET created_at = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE id = {$older['id']}");
        $newer = $this->createProduct();

        $result = $repo->search([]);

        self::assertSame($newer['id'], $result['items'][0]['id']);
    }

    public function testSearchPaginationTotalsAndPages(): void
    {
        $repo = new ProductRepository(self::$pdo);
        for ($i = 0; $i < 7; $i++) {
            $this->createProduct();
        }

        $page1 = $repo->search(['per_page' => 3, 'page' => 1]);
        self::assertSame(7, $page1['total']);
        self::assertSame(3, $page1['pages']);
        self::assertCount(3, $page1['items']);
        self::assertSame(1, $page1['page']);

        $page3 = $repo->search(['per_page' => 3, 'page' => 3]);
        self::assertCount(1, $page3['items']);
    }

    public function testSearchOnlyReturnsActiveProducts(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $active = $this->createProduct(['is_active' => 1]);
        $this->createProduct(['is_active' => 0]);

        $result = $repo->search([]);

        self::assertSame(1, $result['total']);
        self::assertSame($active['id'], $result['items'][0]['id']);
    }

    public function testDecrementStockReducesStockWhenEnoughAvailable(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $product = $this->createProduct(['stock' => 5]);

        self::assertTrue($repo->decrementStock((int) $product['id'], 3));
        self::assertSame(2, (int) $repo->find((int) $product['id'])['stock']);
    }

    public function testDecrementStockFailsAndKeepsStockWhenNotEnough(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $product = $this->createProduct(['stock' => 2]);

        self::assertFalse($repo->decrementStock((int) $product['id'], 3));
        self::assertSame(2, (int) $repo->find((int) $product['id'])['stock']);
    }

    public function testDecrementStockNeverGoesNegativeOnRepeatedCalls(): void
    {
        $repo = new ProductRepository(self::$pdo);
        $product = $this->createProduct(['stock' => 1]);
        $id = (int) $product['id'];

        // Имитация двух покупателей, забирающих последнюю единицу: второй должен получить false.
        self::assertTrue($repo->decrementStock($id, 1));
        self::assertFalse($repo->decrementStock($id, 1));
        self::assertSame(0, (int) $repo->find($id)['stock']);
    }
}
