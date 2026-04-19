<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Allows same-day classes (end after start on the clock) or overnight (e.g. 11:40 PM → 12:45 AM).
 * Rejects only zero-length slots where both times are equal.
 */
class ValidScheduleEndTime implements ValidationRule
{
    public function __construct(
        private readonly mixed $startTime,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($this->startTime) || ! is_string($value)) {
            return;
        }

        if ($this->startTime === $value) {
            $fail('The end time must be after the start time.');
        }
    }
}
