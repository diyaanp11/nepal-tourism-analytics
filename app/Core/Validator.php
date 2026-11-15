<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Validator
{
    /** @var array<string, string[]> */
    private array $errors = [];

    public function __construct(
        private readonly array $data,
        private readonly array $rules
    ) {
        $this->validate();
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            foreach (explode('|', $ruleString) as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }
                $method = 'rule' . ucfirst($rule);
                if (!method_exists($this, $method)) {
                    throw new RuntimeException("Unknown validation rule: {$rule}");
                }
                $this->$method($field, $value, $params);
            }
        }
    }

    private function ruleRequired(string $field, mixed $value, array $params): void
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === null || $value === '') {
            $this->errors[$field][] = $this->label($field) . ' is required.';
        }
    }

    private function ruleEmail(string $field, mixed $value, array $params): void
    {
        if ($value === null || $value === '') return;
        if (!filter_var((string)$value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $this->label($field) . ' must be a valid email address.';
        }
    }

    private function ruleMin(string $field, mixed $value, array $params): void
    {
        if ($value === null || $value === '') return;
        $min = (int)($params[0] ?? 0);
        if (is_string($value) && mb_strlen($value) < $min) {
            $this->errors[$field][] = $this->label($field) . " must be at least {$min} characters.";
        }
    }

    private function ruleMax(string $field, mixed $value, array $params): void
    {
        if ($value === null || $value === '') return;
        $max = (int)($params[0] ?? 0);
        if (is_string($value) && mb_strlen($value) > $max) {
            $this->errors[$field][] = $this->label($field) . " must not exceed {$max} characters.";
        }
    }

    private function ruleSame(string $field, mixed $value, array $params): void
    {
        $other = $params[0] ?? '';
        if ((string)$value !== (string)($this->data[$other] ?? '')) {
            $this->errors[$field][] = $this->label($field) . ' confirmation does not match.';
        }
    }

    private function ruleUnique(string $field, mixed $value, array $params): void
    {
        if ($value === null || $value === '') return;
        $table  = $params[0] ?? '';
        $column = $params[1] ?? $field;
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $table)
            || !preg_match('/^[a-z_][a-z0-9_]*$/', $column)) {
            throw new RuntimeException('Invalid unique rule parameters');
        }
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?"
        );
        $stmt->execute([$value]);
        if ((int)$stmt->fetchColumn() > 0) {
            $this->errors[$field][] = $this->label($field) . ' is already taken.';
        }
    }

    private function label(string $field): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $field));
    }
}