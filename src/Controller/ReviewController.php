<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\ReviewService;

class ReviewController
{
    private const PER_PAGE = 9;

    public function index(Request $request): Response
    {
        $reviewRepo = new ReviewRepository();
        $userRepo = new UserRepository();
        $auth = new Auth($userRepo);

        $page = max(1, (int) $request->query('page', 1));
        $result = $reviewRepo->paginate($page, self::PER_PAGE, 'approved');

        foreach ($result['items'] as &$review) {
            $review['author'] = $userRepo->find((int) $review['user_id']);
        }
        unset($review);

        $approved = $reviewRepo->approved();
        $average = $approved !== []
            ? round(array_sum(array_column($approved, 'rating')) / count($approved), 1)
            : null;

        $canReview = false;
        $reviewBlockReason = null;
        if ($auth->check()) {
            $reviewService = new ReviewService($reviewRepo, new OrderRepository());
            if ($reviewRepo->userReviewExists((int) $auth->id(), null)) {
                $reviewBlockReason = 'Вы уже оставили общий отзыв о магазине. Спасибо!';
            } elseif ($reviewService->canReview((int) $auth->id())) {
                $canReview = true;
            } else {
                $reviewBlockReason = 'Оставить отзыв можно после того, как заказ будет доставлен и завершён.';
            }
        }

        $errors = Session::get('_errors', []);
        Session::remove('_errors');

        $html = View::render('pages/reviews/index', [
            'pageTitle' => 'Отзывы покупателей',
            'reviews' => $result['items'],
            'page' => $result['page'] ?? $page,
            'pages' => $result['pages'],
            'total' => $result['total'],
            'averageRating' => $average,
            'totalApproved' => count($approved),
            'auth' => $auth,
            'canReview' => $canReview,
            'reviewBlockReason' => $reviewBlockReason,
            'errors' => $errors,
        ]);

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        $auth = new Auth(new UserRepository());
        $redirect = $this->safeRedirect((string) $request->input('redirect', ''), '/reviews');

        if (!$auth->check()) {
            Session::flash('error', 'Чтобы оставить отзыв, войдите в аккаунт.');

            return Response::redirect(url('/login'));
        }

        $reviewService = new ReviewService(new ReviewRepository(), new OrderRepository());

        try {
            $reviewService->submit((int) $auth->id(), $request->all());
            Session::flash('success', 'Спасибо! Ваш отзыв отправлен на модерацию и появится на сайте после проверки.');
        } catch (ValidationException $e) {
            Session::flashInput($request->all());
            Session::set('_errors', $e->errors());
            Session::flash('error', 'Не удалось отправить отзыв — проверьте форму.');
        } catch (ForbiddenException $e) {
            Session::flash('error', $e->getMessage());
        }

        return Response::redirect(url($redirect));
    }

    private function safeRedirect(string $path, string $default): string
    {
        if ($path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//')) {
            return $path;
        }

        return $default;
    }
}
