<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repository\UserRepository;

final class RequireGuest
{
    private Auth $auth;

    public function __construct(?Auth $auth = null)
    {
        $this->auth = $auth ?? new Auth(new UserRepository());
    }

    public function __invoke(Request $request): ?Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/');
        }

        return null;
    }
}
