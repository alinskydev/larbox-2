<?php

if (!function_exists('num_round')) {
    function num_round(int|float|null $number): int|float
    {
        return round($number ?: 0, 10);
    }
}

if (!function_exists('num_e')) {
    function num_e(int|float|null $numberOne, int|float|null $numberTwo): bool
    {
        return num_round($numberOne) === num_round($numberTwo);
    }
}

if (!function_exists('num_ne')) {
    function num_ne(int|float|null $numberOne, int|float|null $numberTwo): bool
    {
        return num_round($numberOne) !== num_round($numberTwo);
    }
}

if (!function_exists('num_gt')) {
    function num_gt(int|float|null $numberOne, int|float|null $numberTwo): bool
    {
        return num_round($numberOne) > num_round($numberTwo);
    }
}

if (!function_exists('num_gte')) {
    function num_gte(int|float|null $numberOne, int|float|null $numberTwo): bool
    {
        return num_round($numberOne) >= num_round($numberTwo);
    }
}

if (!function_exists('num_lt')) {
    function num_lt(int|float|null $numberOne, int|float|null $numberTwo): bool
    {
        return num_round($numberOne) < num_round($numberTwo);
    }
}

if (!function_exists('num_lte')) {
    function num_lte(int|float|null $numberOne, int|float|null $numberTwo): bool
    {
        return num_round($numberOne) <= num_round($numberTwo);
    }
}
