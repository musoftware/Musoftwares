<?php

namespace App\Helpers;

class SumHelper
{
    public static $array = [];

    public static function AddValue($array_name, $array_key_name, $value)
    {
        if (! isset(static::$array[$array_name])) {
            static::$array[$array_name] = [];
        }
        if (! isset(static::$array[$array_name][$array_key_name])) {
            static::$array[$array_name][$array_key_name] = [];
        }
        static::$array[$array_name][$array_key_name][] = $value;

        return $value;
    }

    public static function Sum($array_name, $array_key_name)
    {
        if (! isset(static::$array[$array_name])) {
            return 0;
        }
        if (! isset(static::$array[$array_name][$array_key_name])) {
            return 0;
        }

        return array_sum(static::$array[$array_name][$array_key_name]);
    }
}
