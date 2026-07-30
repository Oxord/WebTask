<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Exception\ForbiddenException;
use App\Repository\UserRepository;

final class RequireRole
{
    private Auth $auth;

    public function __construct(private readonly string $permission, ?Auth $auth = null)
    {
        $this->auth = $auth ?? new Auth(new UserRepository());
    }

    public function __invoke(Request $request): ?Response
    {
        if (!$this->auth->check()) {
            return Response::redirect('/login?redirect=' . urlencode($request->path()));
        }

        if (!$this->auth->can($this->permission)) {
            throw new ForbiddenException('Недостаточно прав для доступа.');
        }

        return null;
    }
}
