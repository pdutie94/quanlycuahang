<?php

class Money
{
    public static function parseAmount($value)
    {
        return (float) self::toInt($value);
    }

    public static function parsePrice($value)
    {
        if ($value === null) {
            return '';
        }
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        $int = self::toInt($value);
        if ($int === 0) {
            return '';
        }
        return (float) $int;
    }

    public static function toInt($value)
    {
        if ($value === null) {
            return 0;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }
        $digits = preg_replace('/[^0-9\-]/', '', $value);
        if ($digits === '' || $digits === '-' || $digits === '+') {
            return 0;
        }
        return (int) $digits;
    }

    public static function fromInt($value)
    {
        return (int) $value;
    }

    public static function format($value, $suffix = ' đ')
    {
        $int = self::toInt($value);
        return number_format($int, 0, ',', '.') . $suffix;
    }

    public static function roundDownThousand($value)
    {
        $int = self::toInt($value);
        if ($int <= 0) {
            return 0;
        }
        $rounded = (int) floor($int / 1000) * 1000;
        if ($rounded < 0) {
            $rounded = 0;
        }
        return $rounded;
    }
}
