<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use App\Service\AuthService;

class AuthController
{
    public function showLogin(Request $request): Response
    {
        $errors = Session::get('_errors', []);
        Session::remove('_errors');

        $html = View::render('pages/auth/login', [
            'pageTitle' => 'Вход',
            'errors' => $errors,
            'redirect' => (string) $request->query('redirect', ''),
            'throttled' => (new AuthService(new UserRepository()))->tooManyAttempts(),
        ]);

        return Response::html($html);
    }

    public function login(Request $request): Response
    {
        $authService = new AuthService(new UserRepository());
        $redirectTo = $this->safeRedirect((string) $request->input('redirect', ''));

        if ($authService->tooManyAttempts()) {
            Session::flash('error', 'Слишком много неудачных попыток входа. Попробуйте снова через 15 минут.');

            return Response::redirect(url('/login' . ($redirectTo !== '/account' ? '?redirect=' . rawurlencode($redirectTo) : '')));
        }

        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        $user = $authService->attempt($email, $password);
        if ($user === null) {
            $authService->recordFailedAttempt();
            Session::flashInput($request->all());
            Session::flash('error', 'Неверный email или пароль.');

            return Response::redirect(url('/login' . ($redirectTo !== '/account' ? '?redirect=' . rawurlencode($redirectTo) : '')));
        }

        $authService->clearAttempts();
        (new Auth(new UserRepository()))->login($user);
        Session::flash('success', 'С возвращением, ' . $user['full_name'] . '!');

        return Response::redirect(url($redirectTo));
    }

    public function showRegister(Request $request): Response
    {
        $errors = Session::get('_errors', []);
        Session::remove('_errors');

        $html = View::render('pages/auth/register', [
            'pageTitle' => 'Регистрация',
            'errors' => $errors,
        ]);

        return Response::html($html);
    }

    public function register(Request $request): Response
    {
        $authService = new AuthService(new UserRepository());

        try {
            $user = $authService->register($request->all());
        } catch (ValidationException $e) {
            Session::flashInput($request->all());
            Session::set('_errors', $e->errors());
            Session::flash('error', 'Не удалось зарегистрироваться — проверьте форму.');

            return Response::redirect(url('/register'));
        }

        (new Auth(new UserRepository()))->login($user);
        Session::flash('success', 'Добро пожаловать! Регистрация прошла успешно.');

        return Response::redirect(url('/account'));
    }

    public function logout(Request $request): Response
    {
        (new Auth(new UserRepository()))->logout();
        Session::flash('info', 'Вы вышли из аккаунта.');

        return Response::redirect(url('/'));
    }

    private function safeRedirect(string $path): string
    {
        if ($path !== '' && str_starts_with($path, '/') && !str_starts_with($path, '//')) {
            return $path;
        }

        return '/account';
    }
}
