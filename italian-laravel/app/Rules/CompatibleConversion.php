<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CompatibleConversion implements ValidationRule
{
    public function __construct(private int $maxOperations) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Comma-separated operator/operand pairs consumed by CodeIgniter, never PHP expressions.
        if (! is_string($value) || ! preg_match('/\A[+*\/-],\d+(?:\.\d+)?(?:,[+*\/-],\d+(?:\.\d+)?)*\z/D', $value)) {
            $fail('Use pares operador,valor separados por comas, por ejemplo *,25 o /,1000.');

            return;
        }
        $tokens = explode(',', $value);
        if (count($tokens) > $this->maxOperations * 2) {
            $fail('Este campo admite como máximo '.$this->maxOperations.' operación(es), por compatibilidad con CodeIgniter.');

            return;
        }
        for ($i = 0; $i < count($tokens); $i += 2) {
            $operand = (float) $tokens[$i + 1];
            if (! is_finite($operand) || ($tokens[$i] === '/' && $operand == 0.0)) {
                $fail('Los valores deben ser finitos y no se permite dividir entre cero.');

                return;
            }
        }
    }
}
