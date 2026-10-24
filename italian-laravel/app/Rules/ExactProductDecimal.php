<?php

namespace App\Rules;

use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExactProductDecimal implements ValidationRule
{
    public static function display(mixed $value): string
    {
        return rtrim(rtrim(self::normalize($value), '0'), '.');
    }

    public static function normalize(mixed $value): string
    {
        if (! is_scalar($value) || is_bool($value) || strlen((string) $value) > 200) {
            throw new \InvalidArgumentException('Decimal inválido');
        }
        if (preg_match('/e([+-]?\d+)$/iD', (string) $value, $match) && abs((int) $match[1]) > 200) {
            throw new \InvalidArgumentException('Exponente fuera de rango');
        }
        $number = BigDecimal::of((string) $value)->toScale(24);
        if ($number->abs()->isGreaterThanOrEqualTo(BigDecimal::of('1e41'))) {
            throw new \InvalidArgumentException('Decimal fuera de rango');
        }

        return (string) $number;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            self::normalize($value);
        } catch (\Throwable) {
            $fail('El valor debe caber exactamente en 41 dígitos enteros y 24 decimales, sin redondeo.');
        }
    }
}
