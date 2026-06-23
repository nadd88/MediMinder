<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Minimal server-side validation helper.
 * Collects errors so the controller can return them all at once (422).
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];
    /** @var array<string,mixed> */
    private array $data;

    /** @param array<string,mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, ?string $label = null): self
    {
        $label ??= $field;
        $val = $this->data[$field] ?? null;
        if ($val === null || (is_string($val) && trim($val) === '')) {
            $this->errors[$field] = "{$label} is required.";
        }
        return $this;
    }

    public function email(string $field): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'A valid email address is required.';
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && !in_array($val, $allowed, true)) {
            $this->errors[$field] = $field . ' must be one of: ' . implode(', ', $allowed) . '.';
        }
        return $this;
    }

    public function date(string $field): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', (string) $val);
            if (!$d || $d->format('Y-m-d') !== $val) {
                $this->errors[$field] = $field . ' must be a valid date (YYYY-MM-DD).';
            }
        }
        return $this;
    }

    public function integer(string $field): self
    {
        $val = $this->data[$field] ?? null;
        if ($val !== null && filter_var($val, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field] = $field . ' must be an integer.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
