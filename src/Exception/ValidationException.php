<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly array $errors,
        string $message = 'Ошибка валидации',
    ) {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
