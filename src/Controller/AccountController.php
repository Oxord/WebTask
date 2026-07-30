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
use App\Repository\UserRepository;
use App\Service\AuthService;

class AccountController
{
    public function index(Request $request): Response
    {
        $auth = new Auth(new UserRepository());
        $userId = (int) $auth->id();
        $orders = (new OrderRepository())->forUser($userId);

        $errors = Session::get('_errors', []);
        Session::remove('_errors');

        $html = View::render('pages/account/index', [
            'pageTitle' => 'Личный кабинет',
            'user' => $auth->user(),
            'recentOrders' => array_slice($orders, 0, 5),
            'errors' => $errors,
        ]);

        return Response::html($html);
    }

    public function updateProfile(Request $request): Response
    {
        $auth = new Auth(new UserRepository());
        $authService = new AuthService(new UserRepository());

        try {
            $authService->updateProfile((int) $auth->id(), $request->all());
            Session::flash('success', 'Профиль обновлён.');
        } catch (ValidationException $e) {
            Session::flashInput($request->all());
            Session::set('_errors', $e->errors());
            Session::flash('error', 'Не удалось сохранить профиль — проверьте форму.');
        }

        return Response::redirect(url('/account'));
    }

    public function changePassword(Request $request): Response
    {
        $auth = new Auth(new UserRepository());
        $authService = new AuthService(new UserRepository());

        $current = (string) $request->input('current_password', '');
        $new = (string) $request->input('password', '');
        $confirmation = (string) $request->input('password_confirmation', '');

        if ($new !== $confirmation) {
            Session::set('_errors', ['password_confirmation' => ['Пароли не совпадают.']]);
            Session::flash('error', 'Пароли не совпадают.');

            return Response::redirect(url('/account'));
        }

        try {
            $authService->changePassword((int) $auth->id(), $current, $new);
            Session::flash('success', 'Пароль изменён.');
        } catch (ValidationException $e) {
            Session::set('_errors', $e->errors());
            Session::flash('error', 'Не удалось изменить пароль — проверьте форму.');
        }

        return Response::redirect(url('/account'));
    }

    public function orders(Request $request): Response
    {
        $auth = new Auth(new UserRepository());
        $orders = (new OrderRepository())->forUser((int) $auth->id());

        $html = View::render('pages/account/orders', [
            'pageTitle' => 'Мои заказы',
            'orders' => $orders,
        ]);

        return Response::html($html);
    }

    public function showOrder(Request $request, string $id): Response
    {
        $auth = new Auth(new UserRepository());
        $order = (new OrderRepository())->findWithItems((int) $id);

        if ($order === null || (int) $order['user_id'] !== (int) $auth->id()) {
            throw new NotFoundException('Заказ не найден');
        }

        $html = View::render('pages/account/order', [
            'pageTitle' => 'Заказ № ' . $order['order_number'],
            'order' => $order,
        ]);

        return Response::html($html);
    }
}
