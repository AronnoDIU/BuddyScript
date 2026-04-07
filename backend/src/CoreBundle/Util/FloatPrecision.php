<?php

namespace CoreBundle\Util;

class FloatPrecision
{
    public static function isLt(float $ltVal, float $gtVal, float $fraction = 0.000001): bool
    {
        return ($gtVal - $ltVal) > $fraction;
    }

    public static function isLtOrEq(float $ltOrEq, float $val2): bool
    {
        return $ltOrEq <= $val2;
    }

    public static function isGt(float $gtVal, float $ltVal, float $fraction = 0.000001): bool
    {
        return ($gtVal - $ltVal) > $fraction;
    }

    public static function isGtOrEq(float $gtOrEq, float $val2): bool
    {
        return $gtOrEq >= $val2;
    }

    public static function isEqual(float $val1, float $val2, float $fraction = 0.000001): bool
    {
        return abs($val1 - $val2) <= $fraction;
    }

    public static function isLtZero(float $val, float $fraction = 0.000001): bool
    {
        return \sprintf('%.6f', $val) < $fraction;
    }

    public static function isGtZero(float $val, float $fraction = 0.000001): bool
    {
        return \sprintf('%.6f', $val) > $fraction;
    }
}
