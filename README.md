# PHPivot
A flexible Pivot Table library for PHP.

## Installation

### Via Composer (recommended)

```bash
composer require atellier2/phpivot2
```

### Manual Installation

Clone this repository and run:
```bash
composer install
```

## Usage

```php
<?php
require 'vendor/autoload.php';

use Atellier2\PHPivot\PHPivot;

// @table: an associative array containing rows of columns (like JSON)
$filmsByActorAndGenre = PHPivot::create($data)
        ->setPivotRowFields('Actor')
        ->setPivotColumnFields('Genre')
        ->setPivotValueFields('Genre', PivotConstants::PIVOT_VALUE_COUNT, PivotConstants::DISPLAY_AS_VALUE_AND_PERC_ROW, 'Frequency of Genre in each year')
        ->addFilter('Genre', '', PivotConstants::COMPARE_NOT_EQUAL) //Filter out blanks/unknown genre
        ->generate();
echo $filmsByActorAndGenre->toHtml();
```

## Examples
- Using the film dataset from: https://perso.telecom-paristech.fr/~eagan/class/igr204/datasets
- Go to [examples/Example.php](examples/Example.php) to see how the library is used
- See the output of Example.php [here](https://htmlpreview.github.io/?https://github.com/mhadjimichael/PHPivot/blob/master/examples/Example.php.html)

## Supported Features:
- Nested (infinite) rows and columns
- Sum and Count Functions
- Generate HTML Table
    - Ignore empty rows [ setIgnoreBlankValues ]
- Filters (Equal, Not Equal)
    - Filters support UNIX Wildcards (shell patterns), like \*, ?, [ae], etc. (see php.net/fnmatch )
    - Support for Multiple Values matched as ALL(AND)/OR(ANY)/NONE(NOR) (MATCH_ALL, MATCH_NONE, MATCH_ANY)
    - Additional User-Defined functions as Filters
        - addCustomFilter( user_defined_filter_function, $extra_params = null )
            - @user_defined_filter_function($recordset, $rowID, $extra_params = null) -> should return true whenever a row should be INCLUDED.
    - User-defined "filters" can be setup using calculated columns and regular filters!
- Calculated Columns
    - User defined functions.
    - They can return an array with "key-value" pairs, resulting in multiple calculated columns,named as CALC_COL_NAME_KEY
- Sorting(Ascending by default, Descending, User defined functions)
    - Different Row and Column Sorting methods
    - Can give array argument for multiple level/different sorting
    - User-defined sorting functions
        - @user-defined-sorting-function($a,$b) -> should return $a < $b as boolean
- Display as:
    - Actual Values
    - Percentage of col/row

## Features that need migration to the latest version/TODOs
- Display as: -Percentage of deepest level (@fix)
- Color Coding (background) of data: (@fix)
    - Low->High/High->Low gradient
- "Pivot Comparison" mechanism
