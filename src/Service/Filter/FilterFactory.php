<?php

namespace Atellier2\PHPivot\Service\Filter;

use Atellier2\PHPivot\Config\PivotConstants;

final class FilterFactory
{
    public static function comparison(
        string $column,
        array|string $value,
        int $compare = PivotConstants::COMPARE_EQUAL,
        int $match = PivotConstants::FILTER_MATCH_ALL
    ): FilterInterface {
        return new ComparisonFilter($column, $value, $compare, $match);
    }

    public static function custom(callable $fn, mixed $extraParams = null): FilterInterface
    {
        return new CustomFilter($fn, $extraParams);
    }
}