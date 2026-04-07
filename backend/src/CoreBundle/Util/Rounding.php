<?php

namespace CoreBundle\Util;

/**
 * Class Rounding
 */
class Rounding
{
    public static function roundMoney($value): float
    {
        return round($value, 4);
    }

    public static function roundQuantity($value): float
    {
        return round($value, 6);
    }

    public static function roundTwo($value): float
    {
        return round($value, 2);
    }
}
