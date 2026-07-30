<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Validator;
use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;

class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviews,
        private readonly OrderRepository $orders,
    ) {
    }

    public function canReview(int $userId, ?int $productId = null): bool
    {
        if ($productId === null) {
            return $this->orders->userHasCompletedOrder($userId);
        }

        return $this->orders->userBoughtProduct($userId, $productId);
    }

    public function submit(int $userId, array $data): int
    {
        $productId = isset($data['product_id']) && $data['product_id'] !== ''
            ? (int) $data['product_id']
            : null;

        if (!$this->canReview($userId, $productId)) {
            throw new ForbiddenException('Оставлять отзыв можно только после покупки товара.');
        }

        $validator = Validator::make($data, [
            'rating' => 'required|int|min:1|max:5',
            'body' => 'required|min:10|max:2000',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        if ($this->reviews->userReviewExists($userId, $productId)) {
            throw new ValidationException(['product_id' => ['Вы уже оставили отзыв на этот товар.']]);
        }

        return $this->reviews->create([
            'user_id' => $userId,
            'product_id' => $productId,
            'rating' => (int) $data['rating'],
            'body' => $data['body'],
            'status' => 'pending',
        ]);
    }

    public function moderate(int $reviewId, string $status, int $moderatorId): void
    {
        if (!in_array($status, ['approved', 'rejected'], true)) {
            throw new ValidationException(['status' => ['Недопустимый статус модерации.']]);
        }

        $this->reviews->moderate($reviewId, $status, $moderatorId);
    }
}
