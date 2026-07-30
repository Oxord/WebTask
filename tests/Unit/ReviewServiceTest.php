<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use App\Service\ReviewService;
use PHPUnit\Framework\TestCase;

final class ReviewServiceTest extends TestCase
{
    public function testCanReviewFalseWithoutCompletedOrder(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('userHasCompletedOrder')->willReturn(false);
        $reviews = $this->createMock(ReviewRepository::class);

        $service = new ReviewService($reviews, $orders);

        self::assertFalse($service->canReview(1));
    }

    public function testCanReviewTrueWithCompletedOrder(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('userHasCompletedOrder')->willReturn(true);
        $reviews = $this->createMock(ReviewRepository::class);

        $service = new ReviewService($reviews, $orders);

        self::assertTrue($service->canReview(1));
    }

    public function testProductReviewRequiresThatSpecificProductWasBought(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('userBoughtProduct')->willReturnMap([
            [1, 10, true],
            [1, 99, false],
        ]);
        $reviews = $this->createMock(ReviewRepository::class);

        $service = new ReviewService($reviews, $orders);

        self::assertTrue($service->canReview(1, 10));
        self::assertFalse($service->canReview(1, 99));
    }

    public function testSubmitThrowsForbiddenWhenCannotReview(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('userHasCompletedOrder')->willReturn(false);
        $reviews = $this->createMock(ReviewRepository::class);

        $service = new ReviewService($reviews, $orders);

        $this->expectException(ForbiddenException::class);
        $service->submit(1, ['rating' => 5, 'body' => 'Отличный товар, всем рекомендую!']);
    }

    public function testSubmitRejectsRatingOutOfRange(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('userHasCompletedOrder')->willReturn(true);
        $reviews = $this->createMock(ReviewRepository::class);
        $reviews->method('userReviewExists')->willReturn(false);

        $service = new ReviewService($reviews, $orders);

        $this->expectException(ValidationException::class);
        $service->submit(1, ['rating' => 6, 'body' => 'Отличный товар, всем рекомендую!']);
    }

    public function testSubmitRejectsDuplicateReview(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('userBoughtProduct')->willReturn(true);
        $reviews = $this->createMock(ReviewRepository::class);
        $reviews->method('userReviewExists')->willReturn(true);
        $reviews->expects(self::never())->method('create');

        $service = new ReviewService($reviews, $orders);

        $this->expectException(ValidationException::class);
        $service->submit(1, ['product_id' => 10, 'rating' => 5, 'body' => 'Отличный товар, всем рекомендую!']);
    }

    public function testSubmitCreatesReviewWithPendingStatus(): void
    {
        $orders = $this->createMock(OrderRepository::class);
        $orders->method('userBoughtProduct')->willReturn(true);

        $reviews = $this->createMock(ReviewRepository::class);
        $reviews->method('userReviewExists')->willReturn(false);
        $reviews->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $data): bool {
                return $data['status'] === 'pending'
                    && $data['user_id'] === 1
                    && $data['product_id'] === 10
                    && $data['rating'] === 5;
            }))
            ->willReturn(42);

        $service = new ReviewService($reviews, $orders);
        $id = $service->submit(1, ['product_id' => 10, 'rating' => 5, 'body' => 'Отличный товар, всем рекомендую!']);

        self::assertSame(42, $id);
    }
}
