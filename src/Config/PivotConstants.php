<?php

namespace Atellier2\PHPivot\Config;

final class PivotConstants
{
    public const PIVOT_VALUE_SUM = 1;
    public const PIVOT_VALUE_COUNT = 2;

    public const SORT_ASC = 1;
    public const SORT_DESC = 2;

    public const COMPARE_EQUAL = 1;
    public const COMPARE_NOT_EQUAL = 2;

    public const DISPLAY_AS_VALUE = 0;
    public const DISPLAY_AS_PERC_DEEPEST_LEVEL = 100;
    public const DISPLAY_AS_PERC_COL = 1;
    public const DISPLAY_AS_VALUE_AND_PERC_COL = 2;
    public const DISPLAY_AS_PERC_ROW = 3;
    public const DISPLAY_AS_VALUE_AND_PERC_ROW = 4;
    public const DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL = 5;

    public const TYPE_VAL = 'TYPE_VAL';
    public const TYPE_ROW = 'TYPE_ROW';
    public const TYPE_COL = 'TYPE_COL';
    public const TYPE_COMP = 'TYPE_COMP';

    public const FILTER_MATCH_ALL = 0;
    public const FILTER_MATCH_ANY = 1;
    public const FILTER_MATCH_NONE = 2;

    public const FILTER_PHPIVOT = 0;
    public const FILTER_USER_DEFINED = 1;

    public const COLOR_ALL = 0;
    public const COLOR_BY_ROW = 1;
    public const COLOR_BY_COL = 2;

    public const SYSTEM_FIELDS = ['_type', '_title'];
}