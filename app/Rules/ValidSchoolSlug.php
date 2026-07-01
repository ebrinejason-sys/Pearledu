<?php
namespace App\Rules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
class ValidSchoolSlug implements ValidationRule {
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,38}[a-z0-9])$/', (string) $value)) {
            $fail('3–40 lowercase letters, numbers or hyphens; no leading/trailing hyphen.'); return;
        }
        if (in_array($value, config('tenancy.reserved_subdomains'), true)) $fail('That subdomain is reserved.');
    }
}
