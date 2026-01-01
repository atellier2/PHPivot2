# PHPivot2 Test Suite

This directory contains a comprehensive test suite for the PHPivot2 library. The tests are organized to distinguish between basic unit tests and more advanced integration/business logic tests.

## Test Organization

### Unit Tests (`tests/Unit/`)
Basic tests that verify individual components and features:

#### 1. **BasicInstantiationTest.php**
Tests basic object creation and configuration:
- Creating PHPivot instances with `create()` and constructor
- Setting pivot row and column fields
- Setting pivot value fields
- Method chaining
- Creating from 2D and 1D arrays
- Setting decimal precision
- Setting ignore blank values

#### 2. **InputValidationTest.php**
Tests input validation and exception handling:
- Filter validation (column names, operators, match modes)
- Custom filter validation
- Calculated columns validation
- Sort parameter validation
- Color range validation
- Proper exception throwing for invalid inputs

#### 3. **SecurityTest.php**
Tests XSS prevention and HTML escaping:
- Script tag escaping in data values
- HTML entity escaping in column/row names
- Quote and apostrophe escaping
- Ampersand escaping
- Multiple XSS vector protection
- Unicode character handling
- Safe handling of null values

#### 4. **FilteringTest.php**
Tests data filtering functionality:
- Basic equal and not-equal filters
- Wildcard pattern filters (`*`, `?`, `[ae]`)
- Filtering out blank values
- Multiple filters (AND logic)
- Array filters with MATCH_ALL, MATCH_ANY, MATCH_NONE
- Numeric filters
- Filter chaining

#### 5. **SortingTest.php**
Tests sorting functionality:
- Ascending and descending sort for rows
- Ascending and descending sort for columns
- Custom sort functions
- Natural sorting (handles numbers in strings correctly)
- Multi-level sorting with arrays
- Default sorting behavior

#### 6. **CalculatedColumnsTest.php**
Tests calculated columns feature:
- Simple calculated columns
- Calculated columns returning multiple values (arrays)
- Calculated columns with extra parameters
- Multiple calculated columns
- Using calculated columns in filters

#### 7. **DataFormattingTest.php**
Tests data formatting and display modes:
- DISPLAY_AS_VALUE (plain values)
- DISPLAY_AS_PERC_ROW (percentage of row)
- DISPLAY_AS_VALUE_AND_PERC_ROW (value with row percentage)
- DISPLAY_AS_PERC_COL (percentage of column)
- DISPLAY_AS_VALUE_AND_PERC_COL (value with column percentage)
- Decimal precision settings
- Ignore blank values
- COUNT vs SUM aggregation functions

### Integration Tests (`tests/Integration/`)
Advanced tests that verify complete workflows and real-world scenarios:

#### 1. **PivotGenerationTest.php**
Tests complete pivot table generation:
- Basic pivot table with count
- Pivot table with sum aggregation
- Pivot tables with rows and columns
- Multiple row levels
- Filtering within pivots
- HTML and array output
- Empty datasets
- Complex scenarios with multiple features

#### 2. **HtmlOutputTest.php**
Tests HTML output generation:
- Basic HTML structure (table, thead, tr, th, td)
- HTML with row and column fields
- HTML with custom titles
- Well-formed HTML validation (matching tags)
- 2D array HTML output
- 1D array HTML output
- Percentage display in HTML

#### 3. **EdgeCasesTest.php**
Tests edge cases and boundary conditions:
- Single data point
- Very large numbers
- Negative numbers
- Decimal numbers
- Zero values
- Long strings (1000+ characters)
- Special characters in field names
- Duplicate row names (aggregation)
- All null values
- Percentage calculation with zero sum (division by zero prevention)
- Many columns (50+)
- Many rows (100+)
- Mixed data types in same field

## Running the Tests

### Run All Tests
```bash
vendor/bin/phpunit
```

### Run Unit Tests Only
```bash
vendor/bin/phpunit tests/Unit
```

### Run Integration Tests Only
```bash
vendor/bin/phpunit tests/Integration
```

### Run Specific Test Class
```bash
vendor/bin/phpunit tests/Unit/SecurityTest.php
```

### Run Specific Test Method
```bash
vendor/bin/phpunit --filter testXSSPreventionInDataValues
```

### Run with Verbose Output
```bash
vendor/bin/phpunit --verbose
```

### Run with Code Coverage (requires xdebug)
```bash
vendor/bin/phpunit --coverage-html coverage/
```

## Test Fixtures

Test data fixtures are located in `tests/Fixtures/`:
- `test_data.json` - Sample movie/actor dataset for integration tests

## Test Requirements

- PHP >= 5.6.0 (same as library requirement)
- PHPUnit 5.7, 6.x, 7.x, 8.x, or 9.x
- Composer for dependency management

## Writing New Tests

### Test Structure
All tests should extend `PHPUnit\Framework\TestCase` and follow PSR-4 autoloading:

```php
<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase
{
    protected function setUp(): void
    {
        // Setup code
    }
    
    public function testSomething()
    {
        // Test code
    }
}
```

### Test Naming Conventions
- Test classes: `*Test.php` (e.g., `SecurityTest.php`)
- Test methods: `test*` (e.g., `testXSSPrevention()`)
- Use descriptive names that explain what is being tested

### Assertions
Use PHPUnit's built-in assertions:
- `assertEquals()`, `assertNotEquals()`
- `assertTrue()`, `assertFalse()`
- `assertArrayHasKey()`, `assertArrayNotHasKey()`
- `assertStringContainsString()`, `assertStringNotContainsString()`
- `assertInstanceOf()`
- `expectException()` for exception testing

## Test Coverage

The test suite covers:
- ✅ Object instantiation and configuration
- ✅ Input validation and error handling
- ✅ Security (XSS prevention, HTML escaping)
- ✅ Filtering (patterns, wildcards, multiple filters)
- ✅ Sorting (ascending, descending, custom functions)
- ✅ Calculated columns
- ✅ Data formatting and display modes
- ✅ Complete pivot table generation
- ✅ HTML output
- ✅ Edge cases and boundary conditions

## Continuous Integration

The test suite is designed to work with CI/CD pipelines. Example configurations:

### GitHub Actions
```yaml
- name: Run tests
  run: vendor/bin/phpunit
```

### GitLab CI
```yaml
test:
  script:
    - composer install
    - vendor/bin/phpunit
```

## Best Practices

1. **Keep tests focused**: Each test should verify one specific behavior
2. **Use descriptive names**: Test names should clearly describe what they test
3. **Arrange-Act-Assert**: Structure tests with clear setup, execution, and verification
4. **Test edge cases**: Include tests for boundary conditions and error cases
5. **Avoid test interdependence**: Each test should be independent and runnable in isolation
6. **Use fixtures**: For complex test data, use fixture files
7. **Document complex tests**: Add comments explaining non-obvious test logic

## Contributing

When adding new features to PHPivot2:
1. Write tests first (TDD approach recommended)
2. Ensure all existing tests pass
3. Add new tests for new functionality
4. Update this README if adding new test categories

## Support

For issues or questions about the test suite:
- Review existing test examples
- Check PHPUnit documentation: https://phpunit.de/
- Open an issue on the repository

---

**Test Statistics**: 100+ test cases covering all major features and edge cases.
