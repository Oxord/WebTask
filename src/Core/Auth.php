<?php

declare(strict_types=1);

namespace App\Core;

use App\Repository\UserRepository;

class Auth
{
    /** @var array<string, string[]> */
    private const PERMISSIONS = [
        'admin.access' => ['admin', 'moderator'],
        'products.manage' => ['admin'],
        'categories.manage' => ['admin'],
        'promotions.manage' => ['admin'],
        'users.manage' => ['admin'],
        'orders.manage' => ['admin'],
        'reviews.moderate' => ['admin', 'moderator'],
        'orders.view' => ['admin', 'moderator'],
    ];

    private ?array $userCache = null;
    private bool $userLoaded = false;

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        $this->userCache = $user;
        $this->userLoaded = true;
    }

    public function logout(): void
    {
        Session::remove('user_id');
        Session::remove('user_role');
        $this->userCache = null;
        $this->userLoaded = false;
        Session::regenerate();
    }

    public function check(): bool
    {
        return Session::has('user_id');
    }

    public function id(): ?int
    {
        $id = Session::get('user_id');

        return $id !== null ? (int) $id : null;
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        if (!$this->userLoaded) {
            $this->userCache = $this->users->find((int) $this->id());
            $this->userLoaded = true;
        }

        return $this->userCache;
    }

    public function role(): ?string
    {
        $role = Session::get('user_role');

        return $role !== null ? (string) $role : null;
    }

    public function hasRole(string ...$roles): bool
    {
        $current = $this->role();

        return $current !== null && in_array($current, $roles, true);
    }

    public function can(string $permission): bool
    {
        if (!$this->check()) {
            return false;
        }

        $allowed = self::PERMISSIONS[$permission] ?? null;
        if ($allowed === null) {
            return false;
        }

        return $this->hasRole(...$allowed);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }
}
