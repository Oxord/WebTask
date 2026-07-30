<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Session;
use App\Core\Validator;
use App\Exception\ValidationException;
use App\Repository\UserRepository;

class AuthService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 900; // 15 минут
    private const ATTEMPTS_KEY = '_login_attempts';

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function register(array $data): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|max:190',
            'password' => 'required|min:6',
            'full_name' => 'required|min:2|max:150',
            'phone' => 'phone',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        if ($this->users->emailExists($data['email'])) {
            throw new ValidationException(['email' => ['Пользователь с таким email уже существует.']]);
        }

        $id = $this->users->create([
            'email' => $data['email'],
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'role' => 'user',
            'is_active' => 1,
        ]);

        return $this->users->find($id);
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !(bool) $user['is_active']) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public function updateProfile(int $userId, array $data): array
    {
        $validator = Validator::make($data, [
            'full_name' => 'required|min:2|max:150',
            'phone' => 'phone',
            'address' => 'max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $this->users->update($userId, $validator->validated());

        return $this->users->find($userId);
    }

    public function changePassword(int $userId, string $current, string $new): void
    {
        $user = $this->users->find($userId);
        if ($user === null || !password_verify($current, $user['password_hash'])) {
            throw new ValidationException(['current_password' => ['Текущий пароль указан неверно.']]);
        }

        $validator = Validator::make(['password' => $new], ['password' => 'required|min:6']);
        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $this->users->updatePassword($userId, password_hash($new, PASSWORD_BCRYPT));
    }

    public function tooManyAttempts(): bool
    {
        $data = Session::get(self::ATTEMPTS_KEY);
        if (!is_array($data)) {
            return false;
        }
        if ($data['count'] < self::MAX_ATTEMPTS) {
            return false;
        }

        return (time() - $data['first_at']) <= self::LOCK_SECONDS;
    }

    public function recordFailedAttempt(): void
    {
        $data = Session::get(self::ATTEMPTS_KEY);
        if (!is_array($data) || (time() - $data['first_at']) > self::LOCK_SECONDS) {
            $data = ['count' => 0, 'first_at' => time()];
        }
        $data['count']++;
        Session::set(self::ATTEMPTS_KEY, $data);
    }

    public function clearAttempts(): void
    {
        Session::remove(self::ATTEMPTS_KEY);
    }
}
