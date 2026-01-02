<?php

namespace Atellier2\PHPivot\Exception;

class PHPivotException extends \RuntimeException
{
    public const INVALID_SORT = 0;
    public const INVALID_COLOR = 1;
    public const INVALID_FILTER = 2;
    public const INVALID_PRECISION = 3;
    public const INVALID_CONFIGURATION = 4;
    public const DATA_ERROR = 5;
}
