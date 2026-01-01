<?php
namespace Atellier2\PHPivot\Service\Filter;

interface FilterInterface
{
    public function matches(array $row): bool;
}