<?php

namespace App\Rules;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class ReportDateRange implements DataAwareRule, ValidationRule
{
    public const MAX_DAYS = 90;

    private array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $start = $this->data['desde'] ?? null;
        if (!is_string($start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $start)) {
            return;
        }

        $timezone = new DateTimeZone('UTC');
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $start, $timezone);
        $through = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $timezone);

        if ($from && $through && $from->diff($through)->days >= self::MAX_DAYS) {
            $fail('Selecciona un periodo de hasta '.self::MAX_DAYS.' días, incluyendo la fecha inicial y final.');
        }
    }
}
