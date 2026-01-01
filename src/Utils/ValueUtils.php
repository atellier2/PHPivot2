<?php

namespace Atellier2\PHPivot\Utils;

use Atellier2\PHPivot\Config\PivotConstants;

class ValueUtils
{


    /**
     * Extract actual value from formatted display
     */
    public static function getValueFromFormat($displayValue, $displayMode, $decimalPrecision = 0)
    {
        if (is_null($displayValue)) return $displayValue;

        switch ($displayMode) {
            case PivotConstants::DISPLAY_AS_PERC_DEEPEST_LEVEL:
            case PivotConstants::DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL:
            case PivotConstants::DISPLAY_AS_VALUE:
                break;

            case PivotConstants::DISPLAY_AS_PERC_DEEPEST_LEVEL:
                $a = round(substr($displayValue, 0, strpos($displayValue, '%')), $decimalPrecision);
                break;

            case PivotConstants::DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL:
                $a = round(substr($displayValue, strpos($displayValue, '(') + 1, strpos($displayValue, ')') - 1), $decimalPrecision);
                break;

            default:
                throw new \RuntimeException('getValueFromFormat not programmed to compare display type: ' . $displayMode);
                break;
        }
    }

    /**
     * Get edge value (min/max)
     */
    public static function getEdgeValue($a, $b, $findMax = true)
    {
        if (is_null($a)) return $b;
        if (is_null($b)) return $a;
        return $findMax ? ($a > $b ? $a : $b) : ($a < $b ? $a : $b);
    }

    /**
     * sums up all values in the given data array
     */
    public static function getSumOf(&$d)
    {
        if (!is_array($d)) return 0;

        if (array_key_exists('_val', $d)) {
            return $d['_val'];
        } else {
            $sum = 0;
            foreach ($d as $k => $v) {
                $sum = $sum + self::getSumOf($d[$k]);
            }
            return $sum;
        }
    }
}
