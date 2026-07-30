<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];
    private array $validated = [];

    private function __construct(
        private readonly array $data,
        private readonly array $rules,
    ) {
        $this->run();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $isNumericField = array_intersect(['numeric', 'int'], $rules) !== [];
            $hasRequired = in_array('required', $rules, true);
            $present = $this->isPresent($value);

            foreach ($rules as $rule) {
                $this->applyRule($field, $rule, $value, $present, $isNumericField);
            }

            if ($present || $hasRequired) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function isPresent(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    private function applyRule(string $field, string $rule, mixed $value, bool $present, bool $isNumericField): void
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        // required/same нужно проверять даже для отсутствующих значений, остальные правила
        // для необязательных полей без значения просто пропускаем.
        if (!$present && !in_array($name, ['required', 'same'], true)) {
            return;
        }

        match ($name) {
            'required' => $this->ruleRequired($field, $present),
            'email' => $this->ruleEmail($field, $value),
            'min' => $this->ruleMin($field, $value, (float) $param, $isNumericField),
            'max' => $this->ruleMax($field, $value, (float) $param, $isNumericField),
            'numeric' => $this->ruleNumeric($field, $value),
            'int' => $this->ruleInt($field, $value),
            'in' => $this->ruleIn($field, $value, explode(',', (string) $param)),
            'same' => $this->ruleSame($field, $value, (string) $param, $present),
            'phone' => $this->rulePhone($field, $value),
            'url' => $this->ruleUrl($field, $value),
            'boolean' => $this->ruleBoolean($field, $value),
            'date' => $this->ruleDate($field, $value),
            default => null,
        };
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    private function ruleRequired(string $field, bool $present): void
    {
        if (!$present) {
            $this->addError($field, 'Поле обязательно для заполнения.');
        }
    }

    private function ruleEmail(string $field, mixed $value): void
    {
        if (filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, 'Некорректный email.');
        }
    }

    private function ruleMin(string $field, mixed $value, float $param, bool $isNumericField): void
    {
        if ($isNumericField) {
            if (!is_numeric($value) || (float) $value < $param) {
                $this->addError($field, "Значение должно быть не меньше {$this->fmt($param)}.");
            }

            return;
        }

        if (mb_strlen((string) $value) < $param) {
            $this->addError($field, "Минимальная длина — {$this->fmt($param)} символов.");
        }
    }

    private function ruleMax(string $field, mixed $value, float $param, bool $isNumericField): void
    {
        if ($isNumericField) {
            if (!is_numeric($value) || (float) $value > $param) {
                $this->addError($field, "Значение должно быть не больше {$this->fmt($param)}.");
            }

            return;
        }

        if (mb_strlen((string) $value) > $param) {
            $this->addError($field, "Максимальная длина — {$this->fmt($param)} символов.");
        }
    }

    private function ruleNumeric(string $field, mixed $value): void
    {
        if (!is_numeric($value)) {
            $this->addError($field, 'Значение должно быть числом.');
        }
    }

    private function ruleInt(string $field, mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, 'Значение должно быть целым числом.');
        }
    }

    private function ruleIn(string $field, mixed $value, array $options): void
    {
        if (!in_array((string) $value, $options, true)) {
            $this->addError($field, 'Недопустимое значение.');
        }
    }

    private function ruleSame(string $field, mixed $value, string $otherField, bool $present): void
    {
        $other = $this->data[$otherField] ?? null;
        if (!$present && $other === null) {
            return;
        }
        if ($value !== $other) {
            $this->addError($field, 'Значения должны совпадать.');
        }
    }

    private function rulePhone(string $field, mixed $value): void
    {
        $stringValue = (string) $value;
        $digits = preg_replace('/\D+/', '', $stringValue);

        if (
            !preg_match('/^\+?[\d\s\-\(\)]{5,20}$/', $stringValue)
            || mb_strlen((string) $digits) < 10
            || mb_strlen((string) $digits) > 15
        ) {
            $this->addError($field, 'Некорректный номер телефона.');
        }
    }

    private function ruleUrl(string $field, mixed $value): void
    {
        if (filter_var((string) $value, FILTER_VALIDATE_URL) === false) {
            $this->addError($field, 'Некорректный URL.');
        }
    }

    private function ruleBoolean(string $field, mixed $value): void
    {
        $valid = ['1', '0', 1, 0, true, false, 'true', 'false', 'on', 'off'];
        if (!in_array($value, $valid, true)) {
            $this->addError($field, 'Значение должно быть булевым.');
        }
    }

    private function ruleDate(string $field, mixed $value): void
    {
        if (strtotime((string) $value) === false) {
            $this->addError($field, 'Некорректная дата.');
        }
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
