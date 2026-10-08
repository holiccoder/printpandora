<?php

namespace App\Support;

class WeightFormatter
{
    private const GRAMS_PER_POUND = 453.59237;

    public static function formatGrams(int|float $grams): string
    {
        return number_format($grams / self::GRAMS_PER_POUND, 2)
            .' lbs ('
            .number_format($grams, 0, '.', '')
            .'g)';
    }
}
