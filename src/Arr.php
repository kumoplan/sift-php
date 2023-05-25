<?php

namespace Sift;

class Arr
{
    public static function get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;

        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment)
        {
            if ( ! is_array($array) ||
                ! array_key_exists($segment, $array))
            {
                return self::value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }

    protected static function value($value, ...$args)
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}
