<?php

namespace Atellier2\PHPivot\Exception;

class PHPivotException extends \RuntimeException
{
    public const INVALID_SORT = 'INVALID_SORT';
    public const INVALID_COLOR = 'INVALID_COLOR';
    public const INVALID_FILTER = 'INVALID_FILTER';
    public const INVALID_PRECISION = 'INVALID_PRECISION';
    public const INVALID_CONFIGURATION = 'INVALID_CONFIGURATION';
    public const DATA_ERROR = 'DATA_ERROR';


    private $errorCode;

    public function __construct($message, $errorCode = null, $previous = null)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
