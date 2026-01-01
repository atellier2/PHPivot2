<?php

namespace Atellier2\PHPivot\Service\Filter;

use Atellier2\PHPivot\Config\PivotConstants;

final class ComparisonFilter implements FilterInterface
{
    public function __construct(
        private readonly string $column,
        private readonly array|string $value,
        private readonly int $compare = PivotConstants::COMPARE_EQUAL,
        private readonly int $match = PivotConstants::FILTER_MATCH_ALL
    ) {}

    public function matches(array $row): bool
    {
        $values = is_array($this->value) ? $this->value : [$this->value];
        $results = [];

        foreach ($values as $val) {
            $cmp = $this->compareValue($row[$this->column] ?? null, $val);
            $results[] = $cmp === 0;
        }

        return match ($this->match) {
            PivotConstants::FILTER_MATCH_ALL => !in_array(false, $results, true),
            PivotConstants::FILTER_MATCH_ANY => in_array(true, $results, true),
            PivotConstants::FILTER_MATCH_NONE => !in_array(true, $results, true),
            default => false,
        };
    }

    private function compareValue(mixed $source, mixed $pattern): int
    {
        if (is_numeric($source) && is_numeric($pattern)) {
            return $this->compare === PivotConstants::COMPARE_EQUAL
                ? ($source == $pattern ? 0 : 1)
                : ($source != $pattern ? 0 : 1);
        }

        if (!is_string($source) || !is_string($pattern)) {
            return 1;
        }

        return $this->compare === PivotConstants::COMPARE_EQUAL
            ? (fnmatch($pattern, $source) ? 0 : 1)
            : (!fnmatch($pattern, $source) ? 0 : 1);
    }
}