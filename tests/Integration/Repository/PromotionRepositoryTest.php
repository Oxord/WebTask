<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Repository\PromotionRepository;
use Tests\Support\IntegrationTestCase;

final class PromotionRepositoryTest extends IntegrationTestCase
{
    public function testActiveReturnsOnlyCurrentlyRunningAndActiveFlagged(): void
    {
        $repo = new PromotionRepository(self::$pdo);

        $current = $this->createPromotion([
            'starts_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'is_active' => 1,
        ]);
        $this->createPromotion([
            'starts_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'is_active' => 1,
        ]); // expired
        $this->createPromotion([
            'starts_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+10 days')),
            'is_active' => 1,
        ]); // not started yet
        $this->createPromotion([
            'starts_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'is_active' => 0,
        ]); // inactive flag despite valid dates

        $active = $repo->active();

        self::assertCount(1, $active);
        self::assertSame($current['id'], $active[0]['id']);
    }

    public function testActiveOrdersByEndsAtAscending(): void
    {
        $repo = new PromotionRepository(self::$pdo);

        $endsLater = $this->createPromotion([
            'starts_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+10 days')),
        ]);
        $endsSoon = $this->createPromotion([
            'starts_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
        ]);

        $active = $repo->active();

        self::assertSame($endsSoon['id'], $active[0]['id']);
        self::assertSame($endsLater['id'], $active[1]['id']);
    }

    public function testAttachProductsAndProductIds(): void
    {
        $repo = new PromotionRepository(self::$pdo);
        $promo = $this->createPromotion();
        $p1 = $this->createProduct();
        $p2 = $this->createProduct();

        $repo->attachProducts((int) $promo['id'], [$p1['id'], $p2['id']]);

        $ids = $repo->productIds((int) $promo['id']);
        sort($ids);
        $expected = [(int) $p1['id'], (int) $p2['id']];
        sort($expected);
        self::assertSame($expected, $ids);
    }

    public function testAttachProductsReplacesPreviousSet(): void
    {
        $repo = new PromotionRepository(self::$pdo);
        $promo = $this->createPromotion();
        $p1 = $this->createProduct();
        $p2 = $this->createProduct();

        $repo->attachProducts((int) $promo['id'], [$p1['id']]);
        $repo->attachProducts((int) $promo['id'], [$p2['id']]);

        $ids = $repo->productIds((int) $promo['id']);
        self::assertSame([(int) $p2['id']], $ids);
    }

    public function testActiveDiscountMapReturnsMaxDiscountAcrossMultiplePromotions(): void
    {
        $repo = new PromotionRepository(self::$pdo);
        $product = $this->createProduct();
        $other = $this->createProduct();

        $small = $this->createPromotion(['discount_percent' => 10]);
        $big = $this->createPromotion(['discount_percent' => 35]);
        $repo->attachProducts((int) $small['id'], [$product['id']]);
        $repo->attachProducts((int) $big['id'], [$product['id']]);

        $map = $repo->activeDiscountMap([$product['id'], $other['id']]);

        self::assertSame(35, $map[$product['id']]);
        self::assertArrayNotHasKey($other['id'], $map);
    }

    public function testActiveDiscountMapIgnoresExpiredPromotions(): void
    {
        $repo = new PromotionRepository(self::$pdo);
        $product = $this->createProduct();
        $expired = $this->createPromotion([
            'discount_percent' => 50,
            'starts_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'ends_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);
        $repo->attachProducts((int) $expired['id'], [$product['id']]);

        $map = $repo->activeDiscountMap([$product['id']]);

        self::assertArrayNotHasKey($product['id'], $map);
    }

    public function testFindBySlug(): void
    {
        $repo = new PromotionRepository(self::$pdo);
        $promo = $this->createPromotion(['slug' => 'summer-sale-unique']);

        $found = $repo->findBySlug('summer-sale-unique');

        self::assertNotNull($found);
        self::assertSame($promo['id'], $found['id']);
    }
}
