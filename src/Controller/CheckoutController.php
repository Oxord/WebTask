<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use App\Repository\UserRepository;
use App\Service\CartService;
use App\Service\OrderService;

class CheckoutController
{
    public function index(Request $request): Response
    {
        $cart = $this->cartService();

        if ($cart->isEmpty()) {
            Session::flash('info', 'Ваша корзина пуста — добавьте товары перед оформлением заказа.');

            return Response::redirect(url('/cart'));
        }

        $auth = new Auth(new UserRepository());
        $user = $auth->user();

        $errors = Session::get('_errors', []);
        Session::remove('_errors');

        $html = View::render('pages/checkout/index', [
            'pageTitle' => 'Оформление заказа',
            'items' => $cart->items(),
            'totals' => $cart->totals(),
            'user' => $user,
            'errors' => $errors,
        ]);

        return Response::html($html);
    }

    public function store(Request $request): Response
    {
        $auth = new Auth(new UserRepository());
        $orderService = new OrderService(new OrderRepository(), $this->cartService(), new ProductRepository());

        $customer = [
            'customer_name' => (string) $request->input('customer_name', ''),
            'customer_email' => (string) $request->input('customer_email', ''),
            'customer_phone' => (string) $request->input('customer_phone', ''),
            'shipping_address' => (string) $request->input('shipping_address', ''),
            'comment' => (string) $request->input('comment', ''),
        ];

        try {
            $orderId = $orderService->createFromCart($customer, $auth->id());
        } catch (ValidationException $e) {
            Session::flashInput($request->all());
            Session::set('_errors', $e->errors());
            Session::flash('error', 'Не удалось оформить заказ — проверьте форму.');

            return Response::redirect(url('/checkout'));
        }

        $confirmed = Session::get('checkout_order_ids', []);
        $confirmed[] = $orderId;
        Session::set('checkout_order_ids', $confirmed);

        return Response::redirect(url('/checkout/success/' . $orderId));
    }

    public function success(Request $request, string $id): Response
    {
        $orderId = (int) $id;
        $orders = new OrderRepository();
        $order = $orders->findWithItems($orderId);

        if ($order === null) {
            throw new NotFoundException('Заказ не найден');
        }

        $auth = new Auth(new UserRepository());
        $isOwner = $auth->check() && $order['user_id'] !== null && (int) $order['user_id'] === $auth->id();
        $confirmedInSession = in_array($orderId, Session::get('checkout_order_ids', []), true);

        if (!$isOwner && !$confirmedInSession) {
            throw new NotFoundException('Заказ не найден');
        }

        $html = View::render('pages/checkout/success', [
            'pageTitle' => 'Заказ оформлен',
            'order' => $order,
            'auth' => $auth,
        ]);

        return Response::html($html);
    }

    private function cartService(): CartService
    {
        return new CartService(new ProductRepository(), new PromotionRepository());
    }
}
