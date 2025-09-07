<?php

namespace App\Rules;

use App\Enum\InvoiceStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidInvoiceStatus implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
           // We use the same fromString method from your Enum.
        // If it returns null, it means the status string is invalid.
        if (InvoiceStatus::fromString($value) === null) {
            // This is the clean error message that will be sent back to the user.
            $fail('The selected status is invalid.');
        }
    }
}
