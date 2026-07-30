<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Repository\UserRepository;

final class RequireAuth
{
    private Auth $auth;

    public function __construct(?Auth $auth = null)
    {
        $this->auth = $auth ?? new Auth(new UserRepository());
    }

    public function __invoke(Request $request): ?Response
    {
        if ($this->auth->check()) {
            return null;
        }

        return Response::redirect('/login?redirect=' . urlencode($request->path()));
    }
}
