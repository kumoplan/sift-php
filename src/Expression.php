<?php

namespace Sift;

class Expression
{
    public static function has($fn)
    {
        $method = str_replace('$', 'fn_', $fn);
        return method_exists(__CLASS__, $method);
    }

    public static function fn($fn)
    {
        if (self::has($fn)) {
            $method = str_replace('$', 'fn_', $fn);
            return [__CLASS__, $method];
        }

        throw new BadMethodCallException($fn);
    }

    public static function fn_eq($a, $b)
    {
        return $a === $b;
    }

    public static function fn_ne($a, $b)
    {
        return $a !== $b;
    }

    public static function fn_and($a, $b)
    {
        if (is_array($a)) {
            $valid = array_shift($a);
            foreach ($a as $_a) {
                $valid = $valid && $_a;
                if (!$valid) {
                    return false;
                }
            }
        } else {
            $valid = $a;
        }
        return $valid;
    }

    public static function fn_or($a, $b)
    {
        if (is_array($a)) {
            $valid = array_shift($a);
            foreach ($a as $_a) {
                $valid = $valid || $_a;
                if (!$valid) {
                    return false;
                }
            }
        } else {
            $valid = $a;
        }
        return $valid;
    }

    public static function fn_gt($a, $b)
    {
        return ($a <=> $b) === -1;
    }

    public static function fn_gte($a, $b)
    {
        return ($a <=> $b) <= 0;
    }

    public static function fn_lt($a, $b)
    {
        return ($a <=> $b) === 1;
    }

    public static function fn_lte($a, $b)
    {
        return ($a <=> $b) >= 0;
    }

    public static function fn_in($a, $b)
    {
        return in_array($b, $a, true);
    }

    public static function fn_nin($a, $b)
    {
        return !self::fn_in($a, $b);
    }

    public static function fn_regex($pattern, $value)
    {
        return preg_match($pattern, $value);
    }

    public static function fn_exists($a, $b, $field, $data)
    {
        return array_key_exists($field, $data) === $a;
    }

    public static function fn_not($a, $b)
    {
        if (is_array($a) && is_null($b)) {
            return !self::fn_and($a, null);
        }
        return $a !== $b;
    }

    public static function fn_nor($a, $b)
    {
        return !self::fn_or($a, $b);
    }

    public static function fn_size($a, $b)
    {
        return count($b) === $a;
    }
}
