<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Repository\ReviewRepository;
use Tests\Support\IntegrationTestCase;

final class ReviewRepositoryTest extends IntegrationTestCase
{
    public function testCreateAndFind(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $user = $this->createUser();
        $product = $this->createProduct();

        $id = $repo->create([
            'user_id' => $user['id'],
            'product_id' => $product['id'],
            'rating' => 4,
            'body' => 'Хороший товар, качество на уровне.',
        ]);

        $review = $repo->find($id);
        self::assertNotNull($review);
        self::assertSame(4, (int) $review['rating']);
        self::assertSame('pending', $review['status']);
    }

    public function testApprovedReturnsOnlyApprovedOrderedByCreatedDesc(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $older = $this->createReview(['status' => 'approved']);
        self::$pdo->exec("UPDATE reviews SET created_at = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE id = {$older['id']}");
        $newer = $this->createReview(['status' => 'approved']);
        $this->createReview(['status' => 'pending']);
        $this->createReview(['status' => 'rejected']);

        $approved = $repo->approved();

        self::assertCount(2, $approved);
        self::assertSame($newer['id'], $approved[0]['id']);
        self::assertSame($older['id'], $approved[1]['id']);
    }

    public function testApprovedRespectsLimitAndOffset(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        for ($i = 0; $i < 3; $i++) {
            $this->createReview(['status' => 'approved']);
        }

        $page = $repo->approved(2, 0);
        self::assertCount(2, $page);

        $rest = $repo->approved(2, 2);
        self::assertCount(1, $rest);
    }

    public function testFeaturedReturnsOnlyApprovedAndFeatured(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $featured = $this->createReview(['status' => 'approved', 'is_featured' => 1]);
        $this->createReview(['status' => 'approved', 'is_featured' => 0]);
        $this->createReview(['status' => 'pending', 'is_featured' => 1]);

        $result = $repo->featured(10);

        self::assertCount(1, $result);
        self::assertSame($featured['id'], $result[0]['id']);
    }

    public function testForProductReturnsOnlyApprovedForThatProduct(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $product = $this->createProduct();
        $otherProduct = $this->createProduct();

        $approvedForProduct = $this->createReview(['product_id' => $product['id'], 'status' => 'approved']);
        $this->createReview(['product_id' => $product['id'], 'status' => 'pending']);
        $this->createReview(['product_id' => $otherProduct['id'], 'status' => 'approved']);

        $result = $repo->forProduct((int) $product['id']);

        self::assertCount(1, $result);
        self::assertSame($approvedForProduct['id'], $result[0]['id']);
    }

    public function testModerateSetsStatusModeratorAndDate(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $moderator = $this->createUser(['role' => 'admin']);
        $review = $this->createReview(['status' => 'pending']);

        $repo->moderate((int) $review['id'], 'approved', (int) $moderator['id']);

        $updated = $repo->find((int) $review['id']);
        self::assertSame('approved', $updated['status']);
        self::assertSame($moderator['id'], $updated['moderated_by']);
        self::assertNotNull($updated['moderated_at']);
    }

    public function testToggleFeaturedFlipsFlag(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $review = $this->createReview(['is_featured' => 0]);

        $repo->toggleFeatured((int) $review['id']);
        self::assertSame(1, (int) $repo->find((int) $review['id'])['is_featured']);

        $repo->toggleFeatured((int) $review['id']);
        self::assertSame(0, (int) $repo->find((int) $review['id'])['is_featured']);
    }

    public function testCountByStatus(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $this->createReview(['status' => 'approved']);
        $this->createReview(['status' => 'approved']);
        $this->createReview(['status' => 'pending']);
        $this->createReview(['status' => 'rejected']);

        $counts = $repo->countByStatus();

        self::assertSame(2, $counts['approved']);
        self::assertSame(1, $counts['pending']);
        self::assertSame(1, $counts['rejected']);
    }

    public function testUserReviewExists(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $user = $this->createUser();
        $product = $this->createProduct();
        $this->createReview(['user_id' => $user['id'], 'product_id' => $product['id']]);

        self::assertTrue($repo->userReviewExists((int) $user['id'], (int) $product['id']));
        self::assertFalse($repo->userReviewExists((int) $user['id'], 999999));
    }

    public function testAverageForProductOnlyCountsApproved(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $product = $this->createProduct();

        $this->createReview(['product_id' => $product['id'], 'status' => 'approved', 'rating' => 5]);
        $this->createReview(['product_id' => $product['id'], 'status' => 'approved', 'rating' => 3]);
        $this->createReview(['product_id' => $product['id'], 'status' => 'pending', 'rating' => 1]);

        $average = $repo->averageForProduct((int) $product['id']);

        self::assertEqualsWithDelta(4.0, $average, 0.001);
    }

    public function testAverageForProductReturnsNullWithoutApprovedReviews(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $product = $this->createProduct();
        $this->createReview(['product_id' => $product['id'], 'status' => 'pending']);

        self::assertNull($repo->averageForProduct((int) $product['id']));
    }

    public function testDelete(): void
    {
        $repo = new ReviewRepository(self::$pdo);
        $review = $this->createReview();

        self::assertTrue($repo->delete((int) $review['id']));
        self::assertNull($repo->find((int) $review['id']));
    }
}
