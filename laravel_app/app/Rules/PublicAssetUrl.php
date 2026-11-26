<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PublicAssetUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && (preg_match('#^/(assets|storage|uploads)/[a-zA-Z0-9/_ .-]+$#D', $value) && ! str_contains($value, '..'))) return;
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) return;
        $fail('Đường dẫn phải là URL HTTP/HTTPS hoặc file trong /assets, /storage.');
    }
}
