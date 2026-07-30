<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exception\NotFoundException;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\ReviewService;

class CatalogController
{
    use RendersPage;

    public function index(Request $request): Response
    {
        $categories = new CategoryRepository();
        $products = new ProductRepository();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category_id' => (string) $request->query('category', ''),
            'price_min' => (string) $request->query('price_min', ''),
            'price_max' => (string) $request->query('price_max', ''),
            'in_stock' => $request->query('in_stock') ? 1 : 0,
            'on_sale' => $request->query('on_sale') ? 1 : 0,
            'sort' => (string) $request->query('sort', ''),
            'page' => (int) $request->query('page', 1),
            'per_page' => 12,
        ];

        $result = $products->search($filters);

        return $this->renderPage('pages/catalog/index', [
            'pageTitle' => 'Каталог товаров',
            'products' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $result['page'],
            'filters' => $filters,
            'categories' => $categories->all(),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $productRepo = new ProductRepository();
        $product = $productRepo->findBySlug($slug);
        if ($product === null) {
            throw new NotFoundException('Товар не найден');
        }

        $reviewRepo = new ReviewRepository();
        $userRepo = new UserRepository();
        $auth = new Auth($userRepo);

        $reviews = $reviewRepo->forProduct((int) $product['id']);
        $this->attachReviewAuthors($reviews, $userRepo);

        $canReview = false;
        $reviewBlockReason = null;
        if ($auth->check()) {
            $reviewService = new ReviewService($reviewRepo, new OrderRepository());
            if ($reviewRepo->userReviewExists((int) $auth->id(), (int) $product['id'])) {
                $reviewBlockReason = 'Вы уже оставили отзыв на этот товар. Спасибо!';
            } elseif ($reviewService->canReview((int) $auth->id(), (int) $product['id'])) {
                $canReview = true;
            } else {
                $reviewBlockReason = 'Отзыв можно оставить только после покупки этого товара.';
            }
        }

        $categoryRepo = new CategoryRepository();
        $category = $product['category_id'] ? $categoryRepo->find((int) $product['category_id']) : null;

        $errors = Session::get('_errors', []);
        Session::remove('_errors');

        return $this->renderPage('pages/catalog/show', [
            'pageTitle' => $product['name'],
            'product' => $product,
            'category' => $category,
            'reviews' => $reviews,
            'averageRating' => $reviewRepo->averageForProduct((int) $product['id']),
            'auth' => $auth,
            'canReview' => $canReview,
            'reviewBlockReason' => $reviewBlockReason,
            'errors' => $errors,
        ]);
    }

    /**
     * Загружает авторов пачкой по уникальным user_id вместо find() на каждый отзыв.
     * UserRepository::findMany() нет — batching делаем доступными средствами в контроллере.
     */
    private function attachReviewAuthors(array &$reviews, UserRepository $userRepo): void
    {
        $userIds = array_unique(array_map(static fn (array $r): int => (int) $r['user_id'], $reviews));
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
