<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class RemittanceCost
{
    public static function format(string|int|float $value): string
    {
        $decimal = (string) BigDecimal::of((string) $value)->toScale(3, RoundingMode::HalfUp);
        [$integer, $fraction] = explode('.', $decimal);

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $integer).'.'.$fraction;
    }
}
