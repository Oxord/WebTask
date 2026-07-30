<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exception\ValidationException;
use App\Repository\UserRepository;

// Общая база для контроллеров админ-панели: единая инициализация Auth,
// рендер во вьюхи с layout "admin" и типовые вспомогательные методы для форм.
abstract class AdminController
{
    protected Auth $auth;

    public function __construct()
    {
        $this->auth = new Auth(new UserRepository());
    }

    protected function render(string $view, array $data = []): Response
    {
        $data['auth'] = $this->auth;
        $data['currentUser'] = $this->auth->user();

        return Response::html(View::render('admin/' . $view, $data, 'admin'));
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect(url($path));
    }

    // Чекбоксы не отправляются в POST, если не отмечены — присутствие ключа считаем "включено".
    protected function checkbox(Request $request, string $key): int
    {
        return $request->input($key) !== null ? 1 : 0;
    }

    protected function pageParam(Request $request): int
    {
        return max(1, (int) $request->query('page', 1));
    }

    protected function withValidationErrors(ValidationException $e, Request $request, string $back): Response
    {
        foreach ($e->errors() as $messages) {
            foreach ($messages as $message) {
                Session::flash('error', $message);
            }
        }
        Session::flashInput($request->all());

        return $this->redirect($back);
    }

    protected function intId(string $id): int
    {
        return (int) $id;
    }
}
