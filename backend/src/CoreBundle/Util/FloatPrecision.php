<?php

namespace CoreBundle\Util;

/**
 * Class FloatPrecision
 */
class FloatPrecision
{
    public static function isLt(float $ltVal, float $gtVal, float $fraction = 0.000001): bool
    {
        return ($gtVal - $ltVal) > $fraction;
    }

    public static function isLtOrEq(float $ltOrEq, float $val2): bool
    {
        if ($ltOrEq <= $val2) {
            return true;
        }

        return false;
    }

    public static function isGt(float $gtVal, float $ltVal, float $fraction = 0.000001): bool
    {
        if (($gtVal - $ltVal) > $fraction) {
            return true;
        }

        return false;
    }

    public static function isGtOrEq(float $gtOrEq, float $val2): bool
    {
        if ($gtOrEq >= $val2) {
            return true;
        }

        return false;
    }

    public static function isEqual(float $val1, float $val2, float $fraction = 0.000001): bool
    {
        return abs($val1 - $val2) <= $fraction;
    }

    public static function isLtZero(float $val, float $fraction = 0.000001): bool
    {
        if (\sprintf('%.6f', $val) < $fraction) {
            return true;
        }

        return false;
    }

    public static function isGtZero(float $val, float $fraction = 0.000001): bool
    {
        if (\sprintf('%.6f', $val) > $fraction) {
            return true;
        }

        return false;
    }
}
