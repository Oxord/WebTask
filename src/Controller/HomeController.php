<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\Response;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;

class HomeController
{
    use RendersPage;

    public function index(Request $request): Response
    {
        $products = new ProductRepository();
        $promotions = new PromotionRepository();
        $reviews = new ReviewRepository();

        $featuredReviews = $reviews->featured(3);
        $this->attachReviewAuthors($featuredReviews);

        return $this->renderPage('pages/home', [
            'pageTitle' => 'Главная',
            'featuredProducts' => $products->featured(8),
            'activePromotions' => $promotions->active(),
            'featuredReviews' => $featuredReviews,
        ]);
    }

    /**
     * Загружает авторов пачкой (по уникальным user_id), а не по одному find()
     * на каждый отзыв — это раньше делал сам шаблон home.php.
     */
    private function attachReviewAuthors(array &$reviews): void
    {
        $userIds = array_unique(array_map(static fn (array $r): int => (int) $r['user_id'], $reviews));
        if ($userIds === []) {
            return;
        }

        $userRepo = new UserRepository();
        $usersById = [];
        foreach ($userIds as $userId) {
            $usersById[$userId] = $userRepo->find($userId);
        }

        foreach ($reviews as &$review) {
            $review['author'] = $usersById[(int) $review['user_id']] ?? null;
        }
        unset($review);
    }
}
