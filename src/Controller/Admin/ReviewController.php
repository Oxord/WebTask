<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\ReviewService;

class ReviewController extends AdminController
{
    private ReviewRepository $reviews;
    private UserRepository $users;
    private ProductRepository $products;

    public function __construct()
    {
        parent::__construct();
        $this->reviews = new ReviewRepository();
        $this->users = new UserRepository();
        $this->products = new ProductRepository();
    }

    public function index(Request $request): Response
    {
        $page = $this->pageParam($request);
        $status = (string) $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : '';

        $result = $this->reviews->paginate($page, 20, $status !== '' ? $status : null);

        // ReviewRepository отдаёт только сырые строки reviews — автора и товар
        // подтягиваем отдельно (список короткий за счёт пагинации).
        $items = [];
        foreach ($result['items'] as $review) {
            $user = $this->users->find((int) $review['user_id']);
            $product = $review['product_id'] !== null ? $this->products->find((int) $review['product_id']) : null;
            $items[] = $review + [
                'author_name' => $user['full_name'] ?? 'Пользователь удалён',
                'product_name' => $product['name'] ?? null,
            ];
        }

        return $this->render('reviews/index', [
            'items' => $items,
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $page,
            'status' => $status,
            'counts' => $this->reviews->countByStatus(),
        ]);
    }

    public function moderate(Request $request, string $id): Response
    {
        $reviewId = $this->intId($id);
        $review = $this->reviews->find($reviewId);
        if ($review === null) {
            throw new NotFoundException('Отзыв не найден.');
        }

        $status = (string) $request->input('status', '');

        try {
            (new ReviewService($this->reviews, new OrderRepository()))
                ->moderate($reviewId, $status, (int) $this->auth->id());
            Session::flash('success', $status === 'approved' ? 'Отзыв одобрен.' : 'Отзыв отклонён.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    Session::flash('error', $message);
                }
            }
        }

        return $this->redirect('/admin/reviews');
    }

    public function toggleFeatured(Request $request, string $id): Response
    {
        $reviewId = $this->intId($id);
        $review = $this->reviews->find($reviewId);
        if ($review === null) {
            throw new NotFoundException('Отзыв не найден.');
        }

        $this->reviews->toggleFeatured($reviewId);
        Session::flash('success', ((int) $review['is_featured']) === 1
            ? 'Отзыв снят с главной страницы.'
            : 'Отзыв отмечен как избранный и появится на главной.');

        return $this->redirect('/admin/reviews');
    }

    public function destroy(Request $request, string $id): Response
    {
        $reviewId = $this->intId($id);
        $review = $this->reviews->find($reviewId);
        if ($review === null) {
            throw new NotFoundException('Отзыв не найден.');
        }

        $this->reviews->delete($reviewId);
        Session::flash('success', 'Отзыв удалён.');

        return $this->redirect('/admin/reviews');
    }
}
