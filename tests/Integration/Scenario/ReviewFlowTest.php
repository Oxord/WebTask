<?php

declare(strict_types=1);

namespace Tests\Integration\Scenario;

use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use App\Service\ReviewService;
use Tests\Support\IntegrationTestCase;

final class ReviewFlowTest extends IntegrationTestCase
{
    private function makeService(): array
    {
        $reviews = new ReviewRepository(self::$pdo);
        $orders = new OrderRepository(self::$pdo);

        return [new ReviewService($reviews, $orders), $reviews, $orders];
    }

    public function testCanReviewFalseWithoutCompletedOrderTrueAfter(): void
    {
        [$service, , $orders] = $this->makeService();
        $user = $this->createUser();

        self::assertFalse($service->canReview((int) $user['id']));

        $this->createOrder(['user_id' => $user['id'], 'status' => 'completed']);

        self::assertTrue($service->canReview((int) $user['id']));
    }

    public function testReviewRequiresPurchaseOfThatSpecificProduct(): void
    {
        [$service] = $this->makeService();
        $user = $this->createUser();
        $bought = $this->createProduct();
        $notBought = $this->createProduct();

        $this->createOrder(['user_id' => $user['id'], 'status' => 'completed'], [
            ['product_id' => $bought['id'], 'product_name' => $bought['name'], 'unit_price' => '100.00', 'qty' => 1, 'line_total' => '100.00'],
        ]);

        self::assertTrue($service->canReview((int) $user['id'], (int) $bought['id']));
        self::assertFalse($service->canReview((int) $user['id'], (int) $notBought['id']));
    }

    public function testSubmitThrowsForbiddenWithoutPurchase(): void
    {
        [$service] = $this->makeService();
        $user = $this->createUser();
        $product = $this->createProduct();

        $this->expectException(ForbiddenException::class);
        $service->submit((int) $user['id'], [
            'product_id' => $product['id'],
            'rating' => 5,
            'body' => 'Отличный товар, но я его не покупал.',
        ]);
    }

    public function testSubmitCreatesPendingReviewNotVisibleUntilModerated(): void
    {
        [$service, $reviews] = $this->makeService();
        $user = $this->createUser();
        $product = $this->createProduct();
        $this->createOrder(['user_id' => $user['id'], 'status' => 'completed'], [
            ['product_id' => $product['id'], 'product_name' => $product['name'], 'unit_price' => '100.00', 'qty' => 1, 'line_total' => '100.00'],
        ]);

        $reviewId = $service->submit((int) $user['id'], [
            'product_id' => $product['id'],
            'rating' => 5,
            'body' => 'Отличный товар, всем рекомендую эту покупку!',
        ]);

        $review = $reviews->find($reviewId);
        self::assertSame('pending', $review['status']);

        $approvedForProduct = $reviews->forProduct((int) $product['id']);
        self::assertCount(0, $approvedForProduct);
    }

    public function testDuplicateReviewOnSameProductIsRejected(): void
    {
        [$service] = $this->makeService();
        $user = $this->createUser();
        $product = $this->createProduct();
        $this->createOrder(['user_id' => $user['id'], 'status' => 'completed'], [
            ['product_id' => $product['id'], 'product_name' => $product['name'], 'unit_price' => '100.00', 'qty' => 1, 'line_total' => '100.00'],
        ]);

        $service->submit((int) $user['id'], [
            'product_id' => $product['id'],
            'rating' => 5,
            'body' => 'Первый отзыв на этот товар, всё отлично.',
        ]);

        $this->expectException(ValidationException::class);
        $service->submit((int) $user['id'], [
            'product_id' => $product['id'],
            'rating' => 4,
            'body' => 'Повторный отзыв на тот же товар, должен быть отклонён.',
        ]);
    }

    public function testModerateApprovesAndReviewBecomesPubliclyVisible(): void
    {
        [$service, $reviews] = $this->makeService();
        $moderator = $this->createUser(['role' => 'moderator']);
        $user = $this->createUser();
        $product = $this->createProduct();
        $this->createOrder(['user_id' => $user['id'], 'status' => 'completed'], [
            ['product_id' => $product['id'], 'product_name' => $product['name'], 'unit_price' => '100.00', 'qty' => 1, 'line_total' => '100.00'],
        ]);

        $reviewId = $service->submit((int) $user['id'], [
            'product_id' => $product['id'],
            'rating' => 5,
            'body' => 'Отзыв, который затем будет одобрен модератором.',
        ]);

        self::assertCount(0, $reviews->forProduct((int) $product['id']));

        $service->moderate($reviewId, 'approved', (int) $moderator['id']);

        $public = $reviews->forProduct((int) $product['id']);
        self::assertCount(1, $public);
        self::assertSame($reviewId, $public[0]['id']);

        $stored = $reviews->find($reviewId);
        self::assertSame('approved', $stored['status']);
        self::assertSame($moderator['id'], $stored['moderated_by']);
    }

    public function testModerateRejectsInvalidStatus(): void
    {
        [$service] = $this->makeService();
        $moderator = $this->createUser(['role' => 'admin']);
        $review = $this->createReview();

        $this->expectException(ValidationException::class);
        $service->moderate((int) $review['id'], 'bogus', (int) $moderator['id']);
    }
}
