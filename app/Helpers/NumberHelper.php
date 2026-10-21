<?php

namespace App\Helpers;

class NumberHelper
{
    public static function formatAmount(float $number): string
    {
        return number_format($number, 2, '.', ' ');
    }

    public static function formatAmountWithCurrency(float $number, string $currency = 'UZS'): string
    {
        return static::formatAmount($number) . ($currency ? " $currency" : '');
    }
}
