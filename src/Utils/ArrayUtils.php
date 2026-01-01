<?php
namespace Atellier2\PHPivot\Utils;

use Atellier2\PHPivot\PHPivot;
use Atellier2\PHPivot\Config\PivotConstants;

class ArrayUtils
{
    public static array $SYSTEM_FIELDS = PivotConstants::SYSTEM_FIELDS;

    /**
     * Get array keys excluding system fields
     */
        public static function getPivotKeys(&$array)
    {
        $keys = array();
        if (!is_array($array)) return $keys;
        foreach ($array as $key => $val) {
            if (self::isSystemField($key)) continue;
            array_push($keys, $key);
        }
        return $keys;
    }

    /**
     * Get array values excluding system fields
     */
    public static function getPivotValues(&$array)
    {
        $values = array();
        if (!is_array($array)) return $array;
        foreach ($array as $key => $val) {
            if (self::isSystemField($key)) continue;
            array_push($values, $val);
        }
        return $values;
    }

    public static function isSystemField($fieldName)
    {
        for ($i = 0; $i < count(self::$SYSTEM_FIELDS); $i++) {
            if (strcmp($fieldName, self::$SYSTEM_FIELDS[$i]) == 0) {
                return true;
            }
        }
        return false;
    }
}