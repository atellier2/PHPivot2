<?php

namespace Atellier2\PHPivot\Service\Filter;

use Atellier2\PHPivot\Exception\PHPivotException;

final class CustomFilter implements FilterInterface
{
    /**
     * @param callable $filterFn function(array $row, mixed $extraParams): bool
     */
    public function __construct(
        private readonly mixed $filterFn,
        private readonly mixed $extraParams = null
    ) {
        if (!is_callable($this->filterFn)) {
            throw new PHPivotException(__('error.invalid_filter_function'));
        }
    }

    public function matches(array $row): bool
    {
        return (bool) call_user_func($this->filterFn, $row, $this->extraParams);
    }
}