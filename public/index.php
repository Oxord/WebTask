<?php

declare(strict_types=1);

use App\Controller\AboutController;
use App\Controller\AccountController;
use App\Controller\Admin\CategoryController as AdminCategoryController;
use App\Controller\Admin\DashboardController;
use App\Controller\Admin\MessageController as AdminMessageController;
use App\Controller\Admin\OrderController as AdminOrderController;
use App\Controller\Admin\ProductController as AdminProductController;
use App\Controller\Admin\PromotionController as AdminPromotionController;
use App\Controller\Admin\ReviewController as AdminReviewController;
use App\Controller\Admin\UserController as AdminUserController;
use App\Controller\AuthController;
use App\Controller\CartController;
use App\Controller\CatalogController;
use App\Controller\CheckoutController;
use App\Controller\ContactController;
use App\Controller\HomeController;
use App\Controller\PromotionController;
use App\Controller\ReviewController;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Middleware\RequireAuth;
use App\Middleware\RequireGuest;
use App\Middleware\RequireRole;

require dirname(__DIR__) . '/vendor/autoload.php';

Config::load(dirname(__DIR__) . '/.env');
View::setBasePath(dirname(__DIR__) . '/views');
Session::start();

$router = new Router();

// --- Публичные страницы ---
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [AboutController::class, 'index']);

$router->get('/catalog', [CatalogController::class, 'index']);
$router->get('/catalog/{slug}', [CatalogController::class, 'show']);

$router->get('/promotions', [PromotionController::class, 'index']);
$router->get('/promotions/{slug}', [PromotionController::class, 'show']);

$router->get('/reviews', [ReviewController::class, 'index']);
$router->post('/reviews', [ReviewController::class, 'store'], [RequireAuth::class]);

$router->get('/contacts', [ContactController::class, 'index']);
$router->post('/contacts', [ContactController::class, 'store']);

// --- Корзина и оформление ---
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cart/clear', [CartController::class, 'clear']);

$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'store']);
$router->get('/checkout/success/{id}', [CheckoutController::class, 'success']);

// --- Аутентификация ---
$router->get('/login', [AuthController::class, 'showLogin'], [RequireGuest::class]);
$router->post('/login', [AuthController::class, 'login'], [RequireGuest::class]);
$router->get('/register', [AuthController::class, 'showRegister'], [RequireGuest::class]);
$router->post('/register', [AuthController::class, 'register'], [RequireGuest::class]);
$router->post('/logout', [AuthController::class, 'logout']);

// --- Личный кабинет ---
$router->get('/account', [AccountController::class, 'index'], [RequireAuth::class]);
$router->post('/account/profile', [AccountController::class, 'updateProfile'], [RequireAuth::class]);
$router->post('/account/password', [AccountController::class, 'changePassword'], [RequireAuth::class]);
$router->get('/account/orders', [AccountController::class, 'orders'], [RequireAuth::class]);
$router->get('/account/orders/{id}', [AccountController::class, 'showOrder'], [RequireAuth::class]);

// --- Админ-панель ---
// Middleware создаётся лениво — только для сработавшего маршрута. Если создавать его
// сразу при регистрации, конструктор RequireRole тянет UserRepository, тот открывает
// соединение с БД на каждый запрос, причём до try/catch ниже: недоступная база давала
// сырой fatal error с трассировкой вместо страницы 500.
$role = static fn (string $permission): Closure
    => static fn (Request $request): ?Response => (new RequireRole($permission))($request);

$adminAccess = $role('admin.access');
$manageProducts = $role('products.manage');
$manageCategories = $role('categories.manage');
$managePromotions = $role('promotions.manage');
$manageUsers = $role('users.manage');
$manageOrders = $role('orders.manage');
$viewOrders = $role('orders.view');
$moderateReviews = $role('reviews.moderate');

$router->get('/admin', [DashboardController::class, 'index'], [$adminAccess]);

$router->get('/admin/products', [AdminProductController::class, 'index'], [$manageProducts]);
$router->get('/admin/products/create', [AdminProductController::class, 'create'], [$manageProducts]);
$router->post('/admin/products', [AdminProductController::class, 'store'], [$manageProducts]);
$router->get('/admin/products/{id}/edit', [AdminProductController::class, 'edit'], [$manageProducts]);
$router->post('/admin/products/{id}', [AdminProductController::class, 'update'], [$manageProducts]);
$router->post('/admin/products/{id}/delete', [AdminProductController::class, 'destroy'], [$manageProducts]);

$router->get('/admin/categories', [AdminCategoryController::class, 'index'], [$manageCategories]);
$router->post('/admin/categories', [AdminCategoryController::class, 'store'], [$manageCategories]);
$router->post('/admin/categories/{id}', [AdminCategoryController::class, 'update'], [$manageCategories]);
$router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'destroy'], [$manageCategories]);

$router->get('/admin/promotions', [AdminPromotionController::class, 'index'], [$managePromotions]);
$router->get('/admin/promotions/create', [AdminPromotionController::class, 'create'], [$managePromotions]);
$router->post('/admin/promotions', [AdminPromotionController::class, 'store'], [$managePromotions]);
$router->get('/admin/promotions/{id}/edit', [AdminPromotionController::class, 'edit'], [$managePromotions]);
$router->post('/admin/promotions/{id}', [AdminPromotionController::class, 'update'], [$managePromotions]);
$router->post('/admin/promotions/{id}/delete', [AdminPromotionController::class, 'destroy'], [$managePromotions]);

$router->get('/admin/reviews', [AdminReviewController::class, 'index'], [$moderateReviews]);
$router->post('/admin/reviews/{id}/moderate', [AdminReviewController::class, 'moderate'], [$moderateReviews]);
$router->post('/admin/reviews/{id}/featured', [AdminReviewController::class, 'toggleFeatured'], [$moderateReviews]);
$router->post('/admin/reviews/{id}/delete', [AdminReviewController::class, 'destroy'], [$moderateReviews]);

$router->get('/admin/orders', [AdminOrderController::class, 'index'], [$viewOrders]);
$router->get('/admin/orders/{id}', [AdminOrderController::class, 'show'], [$viewOrders]);
$router->post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus'], [$manageOrders]);

$router->get('/admin/users', [AdminUserController::class, 'index'], [$manageUsers]);
$router->get('/admin/users/create', [AdminUserController::class, 'create'], [$manageUsers]);
$router->post('/admin/users', [AdminUserController::class, 'store'], [$manageUsers]);
$router->get('/admin/users/{id}/edit', [AdminUserController::class, 'edit'], [$manageUsers]);
$router->post('/admin/users/{id}', [AdminUserController::class, 'update'], [$manageUsers]);
$router->post('/admin/users/{id}/delete', [AdminUserController::class, 'destroy'], [$manageUsers]);

$router->get('/admin/messages', [AdminMessageController::class, 'index'], [$adminAccess]);
$router->post('/admin/messages/{id}/read', [AdminMessageController::class, 'markRead'], [$adminAccess]);
$router->post('/admin/messages/{id}/delete', [AdminMessageController::class, 'destroy'], [$adminAccess]);

$request = Request::fromGlobals();

try {
    // Единая точка проверки CSRF: ни один POST не может обойти её, забыв про проверку в контроллере.
    if ($request->isPost() && !Csrf::verify((string) $request->input('_csrf', ''))) {
        throw new ForbiddenException('Недействительный CSRF-токен. Обновите страницу и попробуйте снова.');
    }

    $response = $router->dispatch($request);
} catch (NotFoundException $e) {
    $response = errorResponse(404, 'Страница не найдена', $e->getMessage(), $request);
} catch (ForbiddenException $e) {
    $response = errorResponse(403, 'Доступ запрещён', $e->getMessage(), $request);
} catch (Throwable $e) {
    error_log(sprintf('[%s] %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));

    $detail = Config::isDebug()
        ? $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine()
        : 'Произошла внутренняя ошибка. Мы уже разбираемся.';

    $response = errorResponse(500, 'Внутренняя ошибка', $detail, $request);
}

$response
    ->withHeader('X-Content-Type-Options', 'nosniff')
    ->withHeader('X-Frame-Options', 'SAMEORIGIN')
    ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
    ->send();

function errorResponse(int $status, string $title, string $detail, Request $request): Response
{
    if ($request->isAjax()) {
        return Response::json(['error' => $title, 'message' => $detail], $status);
    }

    try {
        $body = View::render('pages/error', [
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ]);
    } catch (Throwable) {
        $body = '<!doctype html><meta charset="utf-8"><title>' . e($title) . '</title>'
            . '<h1>' . $status . ' — ' . e($title) . '</h1><p>' . e($detail) . '</p>';
    }

    return Response::html($body, $status);
}
