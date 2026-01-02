<?php

namespace Atellier2\PHPivot\Service\Filter;

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
            throw new \InvalidArgumentException('Filter function must be callable');
        }
    }

    public function matches(array $row): bool
    {
        return (bool) call_user_func($this->filterFn, $row, $this->extraParams);
    }
}