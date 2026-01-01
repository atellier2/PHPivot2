<?php

namespace Atellier2\PHPivot;

//PHPivot
// https://github.com/mhadjimichael/PHPivot?tab=readme-ov-file/
/*Supported Features:
    -Can Import "Prepared" 2D Array/Table
    -Nested (infinite) rows and columns
    -Sum and Count Functions
    -Generate HTML Table
        -Ignore empty rows [ setIgnoreBlankValues ]
    -Filters (Equal, Not Equal)
        -Filters support UNIX Wildcards (shell patterns), like *, ?, [ae], etc.
            (see php.net/fnmatch )
        -Support for Multiple Values matched as ALL(AND)/OR(ANY)/NONE(NOR) (MATCH_ALL, MATCH_NONE, MATCH_ANY)
        -@redo: Additional User-Defined functions as Filters
            -addCustomFilter( user_defined_filter_function, $extra_params = null )
                -@user_defined_filter_function($recordset, $rowID, $extra_params = null) -> returns true whenever a row should be INCLUDED.
        -User-defined "filters" can be setup using calculated columns and regular filters!
    -Calculated Columns
        -User defined functions.
            -They can return an array with "key-value" pairs, resulting in multiple calculated columns,
                named as CALC_COL_NAME_KEY
    -Sorting(Ascending by default, Descending, User defined functions)
        -Different Row and Column Sorting methods
        -Can give array argument for multiple level/different sorting
        -User-defined sorting functions
            -@user-defined-sorting-function($a,$b) -> should return $a < $b
    -Display as:
        -Actual Values
        -Percentage of deepest level
    -Color Coding (background) of data:
        -Low->High/High->Low gradient
        @todo:-Comparisons
        @todo:-Color Max (extra color)
        @todo:-Color Min (extra color)
        @todo:-Color average (extra color)
        @todo:-Conditional (Value comparison)+pass function


  @todo
  -Make sure % sums up to exactly 100%
    -http://stackoverflow.com/questions/13483430/how-to-make-rounded-percentages-add-up-to-100

*/


class PHPivot
{
    const PIVOT_VALUE_SUM = 1;
    const PIVOT_VALUE_COUNT = 2;
    const SORT_ASC = 1;
    const SORT_DESC = 2;
    const COMPARE_EQUAL = 1;
    const COMPARE_NOT_EQUAL = 2;

    const DISPLAY_AS_VALUE = 0;
    const DISPLAY_AS_PERC_DEEPEST_LEVEL = 100;
    const DISPLAY_AS_PERC_COL = 1;
    const DISPLAY_AS_VALUE_AND_PERC_COL = 2;
    const DISPLAY_AS_PERC_ROW = 3;
    const DISPLAY_AS_VALUE_AND_PERC_ROW = 4;
    const DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL = 5;


    const TYPE_VAL = 'TYPE_VAL';
    const TYPE_ROW = 'TYPE_ROW';
    const TYPE_COL = 'TYPE_COL';
    const TYPE_COMP = 'TYPE_COMP';

    const FILTER_MATCH_ALL = 0;
    const FILTER_MATCH_ANY = 1;
    const FILTER_MATCH_NONE = 2;

    const FILTER_PHPIVOT = 0;
    const FILTER_USER_DEFINED = 1;


    const COLOR_ALL = 0;
    const COLOR_BY_ROW = 1;
    const COLOR_BY_COL = 2;

    public static $SYSTEM_FIELDS = array('_type', '_title');

    protected $_decimal_precision = 0; //Round to nearest integer by default (0 decimals)

    protected $_recordset;
    protected $_table = array();
    protected $_raw_table = array();
    protected $_calculated_columns = array();
    protected $_values = array();
    protected $_values_functions = array();
    protected $_values_display = null;
    protected $_columns = array();
    protected $_columns_titles = array();
    protected $_columns_sort = self::SORT_ASC;
    protected $_rows = array();
    protected $_rows_titles = array();
    /* array of booleans indicating whether to show sum for each row level */
    protected $_rows_sum = array(); 
    protected $_rows_sort = self::SORT_ASC;
    protected $_ignore_blanks = false;

    protected $_color_by = PHPivot::COLOR_ALL;
    protected $_color_low = null;
    protected $_color_high = null;
    protected $_color_of = array();

    protected $_cache_rows_unique_values = array();
    protected $_cache_columns_unique_values = array();

    protected $_filters = array();

    protected $_source_is_2DTable = false;

    /**
     * Escape HTML special characters to prevent XSS
     * 
     * @param mixed $value The value to escape
     * @return string The escaped value
     */
    private function escapeHtml($value)
    {
        if (is_null($value)) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function create($recordset)
    {
        return new self($recordset);
    }

    public static function createFrom2DArray($recordset, $column_title, $row_desc)
    {
        //@todo: check table completeness?
        //Transform 2D relational array to PHPivot readable format

        $pivotTable = array_merge(array(), $recordset);

        $array_rows = array_keys($pivotTable);
        $count_rows = count($array_rows);

        $array_vals = array_keys($pivotTable[$array_rows[0]]);
        $count_vals = count($array_vals);

        foreach ($pivotTable as $rowName => $rowContent) {
            $pivotTable[$rowName]['_type'] = PHPivot::TYPE_COL;
            foreach ($rowContent as $valueName => $value) {
                $pivotTable[$rowName][$valueName] = array('_type' => PHPivot::TYPE_VAL, '_val' => $value);
            }
        }
        $pivotTable['_type'] = PHPivot::TYPE_ROW;

        //Create a new instance of PHPivot and pass our data in
        $row_titles = $array_rows;
        array_unshift($row_titles, $row_desc .= ' &#8595;');

        $pivot = new self($pivotTable);
        $pivot->set2Dargs($row_titles, $column_title);

        return $pivot;
    }

    public static function createFrom1DArray($recordset, $column_title, $row_desc)
    {
        //@todo: check table completeness?
        //Transform 2D relational array to PHPivot readable format

        $pivotTable = array_merge(array(), $recordset);

        $array_rows = array_keys($pivotTable);
        $count_rows = count($array_rows);

        $pivotTable['_type'] = PHPivot::TYPE_ROW;

        //Create a new instance of PHPivot and pass our data in
        $row_titles = array();
        if (!is_null($row_desc)) {
            array_unshift($row_titles, $row_desc);
        }

        $pivot = new self($pivotTable);
        $pivot->set2Dargs($row_titles, $column_title);

        return $pivot;
    }

    /**
     * Constructor
     * 
     * @param array $recordset The data recordset
     * @throws \InvalidArgumentException if recordset is not an array
     */
    public function __construct($recordset)
    {
        if (!is_array($recordset)) {
            throw new \InvalidArgumentException('Recordset must be an array.');
        }
        $this->_recordset = $recordset;
    }

    public function set2Dargs($row_titles, $column_title)
    {
        $this->_rows_titles = $row_titles;
        $this->_columns_titles = array($column_title . ' &#8594;');
        $this->_source_is_2DTable = true;
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function getRows()
    {
        return $this->_rows;
    }
    public function getRowsTitles()
    {
        return $this->_rows_titles;
    }
    public function getColumns()
    {
        return $this->_columns;
    }
    public function getColumnsTitles()
    {
        return $this->_columns_titles;
    }

    /**
     * Throw a PHPivot exception
     * 
     * @param string $msg The error message
     * @throws \RuntimeException
     */
    private function _error($msg)
    {
        throw new \RuntimeException('PHPivot ERROR: ' . $msg);
    }

    /**
     * define the value fields, their functions and display mode
     * 
     * @param array|string $values The value fields
     * @param array|int $functions The functions to apply to each value field
     * @param int $display The display mode (see DISPLAY_AS_ constants)
     * @param array|string $titles The titles of the value fields (optional)
     */
    public function setPivotValueFields($values, $functions = PHPivot::PIVOT_VALUE_SUM, /*only 1*/ $display = PHPivot::DISPLAY_AS_VALUE, $titles = null)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        if (!is_array($functions)) {
            $functions = array($functions);
        }
        if (count($functions) < count($values)) {
            if (count($functions) == 1) {
                $fn = $functions[0];
                $functions = array_fill(0, count($values), $fn);
            } else {
                $this->_error('Value Fields and Function Count do not match.');
            }
        }
        if (!is_null($titles) && !is_array($titles)) {
            $titles = array($titles);
        }

        $this->_values = $values;
        $this->_values_functions = $functions;
        $this->setDisplayAs($display);
        $this->_columns_titles = $titles; //this fallbacks in case of "0 columns" (that is, only values)

        return $this;
    }


    public function setValueFunctions($functions = PHPivot::PIVOT_VALUE_SUM)
    {
        //@todo: redo with new stuff
        if (!is_array($functions)) {
            $functions = array($functions);
        }
        $this->_values_functions = $functions;
        return $this;
    }

    /**
     * define how to display the values
     * 
     * data could be displayed as: value, percentage of column, percentage of row, percentage of deepest level, etc.
     * 
     * @param int $display The display mode (see DISPLAY_AS_ constants)
     */
    public function setDisplayAs($display = PHPivot::DISPLAY_AS_VALUE)
    {
        $this->_values_display = $display;
        return $this;
    }

    public function setPivotColumnFields($columns, $titles = null)
    {
        if (!is_array($columns)) {
            $columns = array($columns);
        }
        if (is_null(($titles))) {
            $titles = $columns;
        }
        if (!is_array($titles)) {
            $titles = array($titles);
        }
        $this->_columns = $columns;
        $this->_columns_titles = $titles;

        return $this;
    }

    public function setPivotRowFields($rows, $titles = null)
    {
        if (!is_array($rows)) {
            $rows = array($rows);
        }
        if (is_null(($titles))) {
            $titles = $rows;
        }
        if (!is_array($titles)) {
            $titles = array($titles);
        }
        $this->_rows = $rows;
        $this->_rows_titles = $titles;
        return $this;
    }

    public function setIgnoreBlankValues()
    {
        $this->_ignore_blanks = true;
        return $this;
    }

    /**
     * Set the decimal precision for rounding values
     * 
     * @param int $precision The number of decimal places (must be >= 0)
     * @return $this
     * @throws \InvalidArgumentException if precision is negative
     */
    public function setDecimalPrecision($precision)
    {
        if (!is_int($precision) || $precision < 0) {
            throw new \InvalidArgumentException('Decimal precision must be a non-negative integer.');
        }
        $this->_decimal_precision = $precision;
        return $this;
    }

    /**
     * Set color range for color-coding values
     * 
     * @param string $low Low value color (hex format #RRGGBB)
     * @param string $high High value color (hex format #RRGGBB)
     * @param int|null $colorBy How to apply colors (COLOR_ALL, COLOR_BY_ROW, COLOR_BY_COL)
     * @return $this
     * @throws \InvalidArgumentException if color format is invalid
     */
    public function setColorRange($low = '#00af5d', $high = '#ff0017', $colorBy = null)
    {
        // Validate hex color format
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $low)) {
            throw new \InvalidArgumentException('Low color must be in hex format #RRGGBB');
        }
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $high)) {
            throw new \InvalidArgumentException('High color must be in hex format #RRGGBB');
        }
        
        if (is_null($colorBy)) {
            $colorBy = PHPivot::COLOR_ALL;
        }
        
        if (!in_array($colorBy, [self::COLOR_ALL, self::COLOR_BY_ROW, self::COLOR_BY_COL])) {
            throw new \InvalidArgumentException('Invalid colorBy parameter.');
        }
        
        $this->_color_by = $colorBy;
        $this->_color_low = $low;
        $this->_color_high = $high;

        return $this;
    }

    //in case we have no data, we could omit it if flag set
    protected function cleanBlanks(&$point = null)
    {
        if (!$this->_ignore_blanks) return null;

        $countNonBlank = 0;
        if (PHPivot::isDataLevel($point)) {
            if (!is_array($point)) return (!is_null($point) && !empty($point) ? 1 : 0);
            else if (strcmp($point['_type'], PHPivot::TYPE_COMP) == 0) {
                $data_values = PHPivot::pivot_array_values($point);
                for ($i = 0; $i < count($data_values); $i++) {
                    if (!is_null($data_values[$i]) && !empty($data_values[$i])) {
                        $countNonBlank++;
                    }
                }
                return $countNonBlank;
            } else if (strcmp($point['_type'], PHPivot::TYPE_VAL) == 0) {
                $data_values = PHPivot::pivot_array_values($point);
                for ($i = 0; $i < count($data_values); $i++) {
                    if (!is_null($data_values[$i]) && !empty($data_values[$i])) {
                        $countNonBlank++;
                    }
                }
                return $countNonBlank;
            }
        }

        $point_keys = array_keys($point);

        for ($i = count($point_keys) - 1; $i >= 0; $i--) {
            if (PHPivot::isSystemField($point_keys[$i])) continue;

            if ($this->cleanBlanks($point[$point_keys[$i]]) > 0) {
                $countNonBlank++;
            } else if (isset($point['_type']) && strcmp($point['_type'], PHPivot::TYPE_ROW) == 0) {
                unset($point[$point_keys[$i]]);
            }
        }

        return $countNonBlank;
    }

    /**
     * Set the sorting order for columns
     * 
     * @param int|array|callable $sortby Sort order (SORT_ASC, SORT_DESC) or array of sort orders or callable
     * @return $this
     * @throws \InvalidArgumentException if sort parameter is invalid
     */
    public function setSortColumns($sortby)
    {
        if (is_array($sortby)) {
            foreach ($sortby as $sort) {
                if (!is_callable($sort) && !in_array($sort, [self::SORT_ASC, self::SORT_DESC])) {
                    throw new \InvalidArgumentException('Invalid sort value in array.');
                }
            }
        } else if (!is_callable($sortby) && !in_array($sortby, [self::SORT_ASC, self::SORT_DESC])) {
            throw new \InvalidArgumentException('Sort parameter must be SORT_ASC, SORT_DESC, or a callable.');
        }
        
        $this->_columns_sort = $sortby;

        return $this;
    }

    /**
     * Set the sorting order for rows
     * 
     * @param int|array|callable $sortby Sort order (SORT_ASC, SORT_DESC) or array of sort orders or callable
     * @return $this
     * @throws \InvalidArgumentException if sort parameter is invalid
     */
    public function setSortRows($sortby)
    {
        if (is_array($sortby)) {
            foreach ($sortby as $sort) {
                if (!is_callable($sort) && !in_array($sort, [self::SORT_ASC, self::SORT_DESC])) {
                    throw new \InvalidArgumentException('Invalid sort value in array.');
                }
            }
        } else if (!is_callable($sortby) && !in_array($sortby, [self::SORT_ASC, self::SORT_DESC])) {
            throw new \InvalidArgumentException('Sort parameter must be SORT_ASC, SORT_DESC, or a callable.');
        }
        
        $this->_rows_sort = $sortby;

        return $this;
    }

    /**
     * Add calculated columns
     * 
     * The function(s) should be defined as:
     * function user_defined_calculated_column_function($recordset, $rowID, $extra_params = null)
     * and should return the calculated value for that row.
     * 
     * @param array|string $col_name The name(s) of the calculated column(s)
     * @param array|string $calc_function The function(s) to calculate the column(s)
     * @param array|string $extra_params Extra parameters to pass to the function(s) (optional)
     */
    public function addCalculatedColumns($col_name, $calc_function, $extra_params = null)
    {
        if (!is_array($col_name) && !is_array($calc_function)) {
            $col_name = array($col_name);
            $calc_function = array($calc_function);
            $extra_params = array_fill(0, 1, $extra_params);
        } else if (count($col_name) != count($calc_function)) {
            throw new \InvalidArgumentException('addCalculatedColumns: column name and function count mismatch.');
        }
        for ($i = 0; $i < count($col_name); $i++) {
            $calc_col = array();
            $calc_col['name'] = $col_name[$i];

            if (!is_callable($calc_function[$i])) {
                throw new \InvalidArgumentException('Calculated Column function ' . $calc_function[$i] . ' is not callable.');
            }

            $calc_col['function'] = $calc_function[$i];
            $calc_col['extra_params'] = $extra_params[$i];
            array_push($this->_calculated_columns, $calc_col);
        }
        return $this;
    }

    /**
     * Add a filter to the pivot table
     * 
     * @param string $column The column to filter on
     * @param mixed $value The value(s) to filter for
     * @param int $compare Comparison operator (COMPARE_EQUAL or COMPARE_NOT_EQUAL)
     * @param int $match Match mode (FILTER_MATCH_ALL, FILTER_MATCH_ANY, or FILTER_MATCH_NONE)
     * @return $this
     * @throws \InvalidArgumentException if parameters are invalid
     */
    public function addFilter($column, $value, $compare = PHPivot::COMPARE_EQUAL, $match = PHPivot::FILTER_MATCH_ALL)
    {
        if (empty($column) || !is_string($column)) {
            throw new \InvalidArgumentException('Filter column must be a non-empty string.');
        }
        
        if (!in_array($compare, [self::COMPARE_EQUAL, self::COMPARE_NOT_EQUAL])) {
            throw new \InvalidArgumentException('Invalid compare operator.');
        }
        
        if (!in_array($match, [self::FILTER_MATCH_ALL, self::FILTER_MATCH_ANY, self::FILTER_MATCH_NONE])) {
            throw new \InvalidArgumentException('Invalid match mode.');
        }
        
        $filter = array();
        $filter['type'] = PHPivot::FILTER_PHPIVOT;
        $filter['column'] = $column;
        $filter['value'] = $value;
        $filter['compare'] = $compare;
        $filter['match'] = $match;
        array_push($this->_filters, $filter);

        return $this;
    }

    /**
     * Add a custom filter function
     * 
     * The filter function should be defined as:
     * function user_defined_filter_function($recordset, $rowID, $extra_params = null)
     * and should return true whenever a row should be INCLUDED.
     * 
     * @param callable $filterFn The filter function
     * @param mixed $extra_params Extra parameters to pass to the function (optional)
     * @return $this
     * @throws \InvalidArgumentException if the function is not callable
     */
    public function addCustomFilter($filterFn, $extra_params = null)
    {
        if (!is_callable($filterFn)) {
            throw new \InvalidArgumentException('Filter function must be callable.');
        }
        
        $filter = array();
        $filter['type'] = PHPivot::FILTER_USER_DEFINED;
        $filter['function'] = $filterFn;
        $filter['extra_params'] = $extra_params;

        array_push($this->_filters, $filter);
        return $this;
    }

    /**
     * Compare a source value with a pattern
     * 
     * @param mixed $source The source value to compare
     * @param mixed $pattern The pattern to match against
     * @return int 0 if match, -2 if no match
     */
    private function filter_compare($source, $pattern)
    {
        // Numeric comparison
        if (is_numeric($source) && is_numeric($pattern)) {
            return ($source == $pattern ? 0 : -2);
        }
        
        // String comparison with fnmatch (safe for pattern matching)
        // Note: fnmatch is filesystem-based pattern matching and is safe when used with data
        return (fnmatch((string)$pattern, (string)$source) ? 0 : -2);
    }

    //pass data through filters and see if it's a match
    private function isFilterOK($rs_row)
    {
        $filterResult = true;
        for ($i = 0; $i < count($this->_filters) && $filterResult; $i++) {

            switch ($this->_filters[$i]['type']) {

                case PHPivot::FILTER_PHPIVOT:

                    if (is_array($this->_filters[$i]['value'])) {
                        $matches = 0;
                        for ($j = 0; $j < count($this->_filters[$i]['value']); $j++) {
                            switch ($this->_filters[$i]['compare']) {
                                case PHPivot::COMPARE_EQUAL:
                                    $matches += ($this->filter_compare($rs_row[$this->_filters[$i]['column']],  $this->_filters[$i]['value'][$j]) == 0 ? 1 : 0);
                                    break;
                                case PHPivot::COMPARE_NOT_EQUAL:
                                    $matches = ($this->filter_compare($rs_row[$this->_filters[$i]['column']],  $this->_filters[$i]['value'][$j]) == 0 ? 0 : 1);
                                    if (!$filterResult) return $filterResult;
                                    break;
                                default:
                                    throw new \RuntimeException('PHPivot: Compare function ' . $this->_filters[$i]['compare'] . ' not defined.');
                                    break;
                            }
                        }

                        switch ($this->_filters[$i]['match']) {
                            case PHPivot::FILTER_MATCH_ALL:
                                $filterResult = $filterResult && ($matches == count($this->_filters[$i]['value']));
                                if (!$filterResult) return $filterResult;
                                break;
                            case PHPivot::FILTER_MATCH_NONE:
                                $filterResult = $filterResult && ($matches == 0);
                                if (!$filterResult) return $filterResult;
                                break;
                            case PHPivot::FILTER_MATCH_ANY:
                                $filterResult = $filterResult && ($matches > 0);
                                if (!$filterResult) return $filterResult;
                                break;
                            default:
                                throw new \RuntimeException('PHPivot: FILTER_MATCH function ' . $this->_filters[$i]['match'] . ' not defined.');
                                break;
                        }
                    } else {
                        if (!isset($rs_row[$this->_filters[$i]['column']])) {
                            throw new \RuntimeException('PHPivot: Filter: No such column ' . $this->_filters[$i]['column']);
                        }
                        switch ($this->_filters[$i]['compare']) {
                            case PHPivot::COMPARE_EQUAL:
                                $filterResult = $filterResult && ($this->filter_compare($rs_row[$this->_filters[$i]['column']],  $this->_filters[$i]['value']) == 0 ? true : false);
                                if (!$filterResult) return $filterResult;
                                break;
                            case PHPivot::COMPARE_NOT_EQUAL:
                                $filterResult = $filterResult && ($this->filter_compare($rs_row[$this->_filters[$i]['column']],  $this->_filters[$i]['value']) != 0 ? true : false);
                                if (!$filterResult) return $filterResult;
                                break;
                            default:
                                throw new \RuntimeException('PHPivot: Compare function ' . $this->_filters[$i]['compare'] . ' not defined.');
                                break;
                        }
                    }
                    break;
                case PHPivot::FILTER_USER_DEFINED:
                    //@redo @todo (take into account FILTER_MATCH_ALL/NONE/ANY + COMPARE_EQUAL/NOT_EQUAL)
                    //$new_col_vals = call_user_func( $col_fn, $this->_recordset, $i );
                    $this->_error('User defined filters not yet implemented!');
                    $filterResult = $filterResult && call_user_func($this->_recordset, $rs_i, $this->_filters[$i]['extra_params']);
                    break;
                default:
                    throw new \RuntimeException('Undefined Filter Type: ' . $this->_filters[$i]['_type']);
                    break;
            }
        }
        return $filterResult;
    }

    /**
     * Produce calculated columns
     * 
     * 
     */
    protected function calculateColumns()
    {
        $recordset_rows = count($this->_recordset);

        foreach ($this->_calculated_columns as $calc_col) {
            $col_name = $calc_col['name'];
            $col_fn = $calc_col['function'];
            $extra_params = $calc_col['extra_params'];

            for ($i = 0; $i < $recordset_rows; $i++) {
                if (!empty($extra_params)) {
                    $new_col_vals = call_user_func($col_fn, $this->_recordset, $i, $extra_params);
                } else {
                    $new_col_vals = call_user_func($col_fn, $this->_recordset, $i);
                }
                if (!is_array($new_col_vals)) {
                    $this->_recordset[$i][$col_name] = $new_col_vals;
                } else {
                    foreach ($new_col_vals as $key => $val) {
                        $this->_recordset[$i][$col_name . '_' . $key]  = $val;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * generate the pivot table; internal representation
     */
    public function generate()
    {
        $table = array();

        if (empty($this->_recordset)) {
            return $table;
        }
        if (!$this->_source_is_2DTable) {
            //Calculate all CALCULATED COLUMNS
            $this->calculateColumns();

            //Find all rows' and columns' unique "labels"

            //Initialize with an empty list for each row and column
            $rows_unique_values = &$this->_cache_rows_unique_values;
            for ($i = 0; $i < count($this->_rows); $i++) {
                $rows_unique_values[$this->_rows[$i]] = array();
            }
            $columns_unique_values = &$this->_cache_columns_unique_values;
            for ($i = 0; $i < count($this->_columns); $i++) {
                $columns_unique_values[$this->_columns[$i]] = array();
            }

            //Iterate through the dataset and add the unique values of interest to the respective arrays
            foreach ($this->_recordset as $rs_ind => $rs_row) {
                if (!$this->isFilterOK($rs_row)) continue; //Excluded due to filter
                foreach ($this->_columns as $col) {
                    if (!in_array($rs_row[$col], $columns_unique_values[$col])) {
                        array_push($columns_unique_values[$col], $rs_row[$col]);
                    }
                }
                foreach ($this->_rows as $row_title) {
                    if (!in_array($rs_row[$row_title], $rows_unique_values[$row_title])) {
                        array_push($rows_unique_values[$row_title], $rs_row[$row_title]);
                    }
                }
            }

            //Sort columns and rows names
            foreach ($this->_columns as $index => $col) {
                $sort = $this->_columns_sort;
                if (is_array($this->_columns_sort)) {
                    if (isset($this->_columns_sort[$index])) {
                        $sort = $this->_columns_sort[$index];
                    } else {
                        $sort = PHPivot::SORT_ASC;
                    }
                }
                if ($sort == PHPivot::SORT_ASC || $sort == PHPivot::SORT_DESC) { //Natural compare algorithm
                    natsort($columns_unique_values[$col]);
                    if ($sort == PHPivot::SORT_DESC) {
                        array_reverse($columns_unique_values[$col]);
                    }
                } else { //User defined sort algorithm
                    usort($columns_unique_values[$col], $sort);
                }
            }
            foreach ($this->_rows as $index => $row) {
                $sort = $this->_rows_sort;
                if (is_array($this->_rows_sort)) {
                    if (isset($this->_rows_sort[$index])) {
                        $sort = $this->_rows_sort[$index];
                    } else {
                        $sort = PHPivot::SORT_ASC;
                    }
                }
                if ($sort == PHPivot::SORT_ASC || $sort == PHPivot::SORT_DESC) { //Natural compare algorithm
                    natsort($rows_unique_values[$row]);
                    if ($this->_rows_sort == PHPivot::SORT_DESC) {
                        array_reverse($rows_unique_values[$row]);
                    }
                } else { //User defined sort algorithm
                    usort($rows_unique_values[$row], $sort);
                }
            }

            //Create an associative array with all the value fields (for all rows)
            $values_assoc = array();
            for ($i = 0; $i < count($this->_values); $i++) {
                $new_values_assoc = array();
                $new_values_assoc['_type'] = PHPivot::TYPE_VAL;
                //$new_values_assoc['_title'] = $this->_values[$i]; // not needed anymore
                $new_values_assoc['_val'] = null;
                $values_assoc[$this->_values[$i]] = $new_values_assoc;
            }

            //Create an associative array with all the unique values for all the columns
            $columns_assoc = $values_assoc;
            for ($i = count($this->_columns) - 1; $i >= 0; $i--) {
                $new_columns_assoc = array();
                $new_columns_assoc['_type'] = PHPivot::TYPE_COL;

                $cur_col_values = $columns_unique_values[$this->_columns[$i]];
                foreach ($cur_col_values as $index => $value) {
                    $new_columns_assoc[$value] = $columns_assoc;
                }
                $columns_assoc = $new_columns_assoc;
            }

            //Create an associative array with all the unique values for all the rows
            $rows_assoc = $columns_assoc; //Each row starts with all the columns
            for ($i = count($this->_rows) - 1; $i >= 0; $i--) {
                $new_rows_assoc = array();
                $new_rows_assoc['_type'] = PHPivot::TYPE_ROW;
                $new_columns_assoc['_title'] = $this->_rows_titles[$i];
                $cur_row_values = $rows_unique_values[$this->_rows[$i]];
                foreach ($cur_row_values as $key => $value) {
                    $new_rows_assoc[$value] = $rows_assoc;
                }
                $rows_assoc = $new_rows_assoc;
            }
            $table = $rows_assoc;

            //Iterate throughout the recordset and fill the table
            foreach ($this->_recordset as $rs_ind => $rs_row) {
                if (!$this->isFilterOK($rs_row)) continue; //Excluded due to filter
                //Traverse and find the right row and column
                $top_point = &$table;
                for ($i = 0; $i < count($this->_rows); $i++) {
                    $top_point = &$top_point[$rs_row[$this->_rows[$i]]];
                }
                for ($i = 0; $i < count($this->_columns); $i++) {
                    $top_point = &$top_point[$rs_row[$this->_columns[$i]]];
                }

                //Record current data (depends on our PIVOT_VALUE function)
                foreach ($this->_values as $val_ind => $val) {
                    $point = &$top_point[$val];
                    $value_point = &$point['_val'];
                    $point['_type'] = PHPivot::TYPE_VAL; //make sure we "label" this as a value level array (needed for "no columns" cases)
                    $value_function = $this->_values_functions[$val_ind];

                    switch ($value_function) {
                        case PHPivot::PIVOT_VALUE_COUNT:
                            if (is_null($value_point)) {
                                $value_point = 1;
                            } else {
                                $value_point = $value_point + 1;
                            }
                            break;

                        case PHPivot::PIVOT_VALUE_SUM:
                            if (is_null($value_point) && !is_null($rs_row[$val])) {
                                $value_point = $rs_row[$val];
                            } else {
                                $value_point += $rs_row[$val];
                            }
                            break;

                        default:
                            throw new \RuntimeException('Value function not defined in PHPivot: ' . $value_function);
                            break;
                    }
                }
            }

            $this->cleanBlanks($table);
        } else {
            //Source was a 2D table (prepared)
            $table = $this->_recordset;
        }

        $this->_raw_table = array_merge(array(), $table); //Clone array to "raw table" (used for comparisons)
        $this->formatData($table);
        $this->colorData($table);

        $this->_table = $table;
        return $this;
    }

    protected static function isSystemField($fieldName)
    {
        for ($i = 0; $i < count(PHPivot::$SYSTEM_FIELDS); $i++) {
            if (strcmp($fieldName, PHPivot::$SYSTEM_FIELDS[$i]) == 0) {
                return true;
            }
        }
        return false;
    }

    protected static function isDeepestLevel(&$row)
    {
        foreach ($row as $key => $child) {
            if (PHPivot::isSystemField($key)) continue;
            if (isset($row[$key]['_type'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * check if we're at data level
     */
    protected static function isDataLevel(&$row)
    {
        return !is_array($row) || (isset($row['_type']) &&
            (strcmp($row['_type'], PHPivot::TYPE_VAL) == 0 ||
                strcmp($row['_type'], PHPivot::TYPE_COMP) == 0));
    }

    //returns actual value even when formatted
    //@consider: maybe just keep it separate?
    private function getValueFromFormat($a)
    {
        if (is_null($a)) return $a;
        switch ($this->_values_display) {
            //@todo multi-value
            case PHPivot::DISPLAY_AS_PERC_DEEPEST_LEVEL:
            case PHPivot::DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL:
            case PHPivot::DISPLAY_AS_VALUE:
                break;

            case PHPivot::DISPLAY_AS_PERC_DEEPEST_LEVEL:
                $a = round(substr($a, 0, strpos($a, '%')), $this->_decimal_precision);
                break;

            case PHPivot::DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL:
                $a = round(substr($a, strpos($a, '(') + 1, strpos($a, ')') - 1), $this->_decimal_precision);
                break;

            default:
                throw new \RuntimeException('getValueFromFormat not programmed to compare display type: ' . $this->_values_display);
                break;
        }
        return $a;
    }

    private function getEdgeValue($a, $b, $findMax = true)
    {
        if (is_null($a)) return $b;
        if (is_null($b)) return $a;

        if ($findMax) {
            return ($a > $b ? $a : $b);
        } else {
            return ($a < $b ? $a : $b);
        }
    }

    private function findMax(&$row, $findMax = true)
    {
        if (PHPivot::isDataLevel($row)) {
            $v = PHPivot::pivot_array_values($row);
            $find = null;
            if (empty($v)) return null;

            if (is_array($v)) {
                $find = $this->getValueFromFormat($v[0]);
                for ($i = 1; $i < count($v); $i++) {
                    $find = $this->getEdgeValue($find, $this->getValueFromFormat($v[$i]), $findMax);
                }
            } else {
                $find = $this->getValueFromFormat($v);
            }
            return $find;
        } else {
            $find = null;
            $k = PHPivot::pivot_array_keys($row);
            for ($i = 0; $i < count($k); $i++) {
                $find = $this->getEdgeValue($find, PHPivot::findMax($row[$k[$i]], $findMax), $findMax);
            }
            return $find;
        }
    }

    private function findMin(&$row)
    {
        return $this->findMax($row, false);
    }

    private static function hexToRGB($hex)
    {
        $hex = str_replace('#', '', $hex);
        $rgb = array();
        $rgb['r'] = hexdec(substr($hex, 0, 2));
        $rgb['g'] = hexdec(substr($hex, 2, 2));
        $rgb['b'] = hexdec(substr($hex, 4, 2));
        return $rgb;
    }

    private static function toHexColor($RGB)
    {
        return sprintf('%02x', ($RGB['r'])) . sprintf('%02x', ($RGB['g'])) . sprintf('%02x', ($RGB['b']));
    }

    //Used when coloring, gives color in html (for hex)
    private function getColorOf($value)
    {
        return 'inherit'; //@todo: temporarily disabled
        //@todo multi-value
        switch ($this->_color_by) {
            case PHPivot::COLOR_ALL:
                $v = $this->getValueFromFormat($value);
                if (isset($this->_color_of[$v]))
                    return $this->_color_of[$v];
                else
                    return 'inherit';
                break;
            default:
                throw new \RuntimeException('getColorOf not programmed to handle COLOR_BY=' . $this->_color_by);
                break;
        }
    }

    //@todo: needs re-implementation
    //picks a color for each cell based on value
    private function colorData(&$row, $row_name = null)
    {
        return; //@TODO
        if (!isset($this->_color_low)) return;
        switch ($this->_color_by) {
            case PHPivot::COLOR_ALL:
                //1. Find Min and Max Values
                $min = $this->findMin($row);
                $max = $this->findMax($row);
                // /*@debug */ echo "min=$min and max=$max<br />";
                if ($min == $max) return; //Don't color if they're the same!
                $stops = $max - $min + 1;
                //2. Calculate colors from min to max value (gradient)
                //@todo: Bezier increments (smoother gradients)
                //NOTE: Another approach would be linear interpolation between 2 colors?
                //http://bsou.io/posts/color-gradients-with-python
                $fromColor = PHPivot::hexToRGB($this->_color_low);
                $toColor = PHPivot::hexToRGB($this->_color_high);
                $stepBy = array(
                    'r' => (($fromColor['r'] - $toColor['r']) / ($stops - 1)),
                    'g' => (($fromColor['g'] - $toColor['g']) / ($stops - 1)),
                    'b' => (($fromColor['b'] - $toColor['b']) / ($stops - 1))
                );
                $curColor = array_merge(array(), $fromColor);

                for ($i = $min; $i <= $max; $i++) {
                    $this->_color_of[$i] = 'rgba(' . $curColor['r'] . ',' . $curColor['g'] . ',' . $curColor['b'] . ',0.8)';
                    $curColor['r'] = floor($fromColor['r'] - $stepBy['r'] * $i);
                    $curColor['g'] = floor($fromColor['g'] - $stepBy['g'] * $i);
                    $curColor['b'] = floor($fromColor['b'] - $stepBy['b'] * $i);
                }

                break;
            case PHPivot::COLOR_BY_ROW:
                //@todo
                throw new \RuntimeException('PHPivot: COLOR_BY_ROW not yet implemented.');
                break;
            case PHPivot::COLOR_BY_COL:
                //@todo
                throw new \RuntimeException('PHPivot: COLOR_BY_COL not yet implemented.');
                break;
            default:
                throw new \RuntimeException('PHPivot: Cannot color data by ' . $this->_color_by);
                break;
        }
    }

    /**
     * sums up all values in the given data array
     */
    private function getSumOf(&$d)
    {
        if (!is_array($d)) return 0;

        if (array_key_exists('_val', $d)) {
            return $d['_val'];
        } else {
            $sum = 0;
            foreach ($d as $k => $v) {
                $sum = $sum + $this->getSumOf($d[$k]);
            }
            return $sum;
        }
    }

    /**
     * Calculates the percentage out of sum given, sets the value (or appends)
     * making the _val field "3 (23%)" or "23%"
     * 
     * @param array $d The data array to process
     * @param float|int $sum The sum to calculate percentage from
     * @param bool $keepValue Whether to keep the original value
     */
    private function setAsPercOf(&$d, $sum, $keepValue = false)
    {
        if (!is_array($d)) return;
        if ($sum == 0) return; // Avoid division by zero

        if (array_key_exists('_val', $d)) {
            $actual_value = $d['_val'];
            if (empty($actual_value)) {
                $actual_value = 0;
            }

            $d['_val'] = round($actual_value * 100 / $sum, $this->_decimal_precision);

            if ($keepValue) {
                $d['_val'] .= '% (' . $actual_value . ')';
            }
        } else {
            foreach ($d as $k => $v) {
                $this->setAsPercOf($d[$k], $sum, $keepValue);
            }
        }
    }

    //Formats the values as requested in class variable "_values_display" (e.g. % by column)
    private function formatData(&$row)
    {
        switch ($this->_values_display) {
            case PHPivot::DISPLAY_AS_VALUE:
                return;
                break;

            case PHPivot::DISPLAY_AS_VALUE_AND_PERC_ROW:
            case PHPivot::DISPLAY_AS_PERC_ROW:
                //Empty table
                if (!is_array($row)) return;

                //BFS and reach the deepest row
                if (!empty(($row)) && array_key_exists('_type', $row) && strcmp($row['_type'], PHPivot::TYPE_ROW) == 0) {
                    $keys = array_keys($row);
                    $keycount = count($keys);
                    for ($i = 0; $i < $keycount; $i++) {
                        $this->formatData($row[$keys[$i]]);
                    }
                    return;
                }

                //We are at columns level:
                //Sum up all VALUES
                $sum = $this->getSumOf($row);

                $keepValue = false;
                switch ($this->_values_display) {
                    case PHPivot::DISPLAY_AS_VALUE_AND_PERC_ROW:
                        $keepValue = true;
                        break;
                }

                //Calculate % of sum for each value:
                $this->setAsPercOf($row, $sum, $keepValue);
                break;


            case PHPivot::DISPLAY_AS_VALUE_AND_PERC_COL:
            case PHPivot::DISPLAY_AS_PERC_COL:
                //Empty table
                if (!is_array($row)) return;

                //BFS and reach the deepest COL
                if (!empty(($row)) && array_key_exists('_type', $row) && strcmp($row['_type'], PHPivot::TYPE_COL) == 0) {
                    $keys = array_keys($row);
                    $keycount = count($keys);
                    for ($i = 0; $i < $keycount; $i++) {
                        $this->formatData($row[$keys[$i]]);
                    }
                    return;
                }

                //We are at columns level:
                //Sum up all VALUES
                $sum = $this->getSumOf($row);

                $keepValue = false;
                switch ($this->_values_display) {
                    case PHPivot::DISPLAY_AS_VALUE_AND_PERC_COL:
                        $keepValue = true;
                        break;
                }

                //Calculate % of sum for each value:
                $this->setAsPercOf($row, $sum, $keepValue);

                break;

            //@todo
            case PHPivot::DISPLAY_AS_PERC_DEEPEST_LEVEL:
            case PHPivot::DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL:
                // Note: DISPLAY_AS_PERC_DEEPEST_LEVEL needs re-implementation. Displaying plain values.
                break;
            default:
                $this->_error('Cannot format data as: ' . $this->_values_display);
                break;
        }
    }

    public function toArray()
    {
        return $this->_table;
    }

    public function toRawArray()
    {
        return $this->_raw_table;
    }

    protected static function pivot_array_keys(&$array)
    {
        $keys = array();
        if (!is_array($array)) return $keys;
        foreach ($array as $key => $val) {
            if (PHPivot::isSystemField($key)) continue;
            array_push($keys, $key);
        }
        return $keys;
    }

    protected static function pivot_array_values(&$array)
    {
        $values = array();
        if (!is_array($array)) return $array;
        foreach ($array as $key => $val) {
            if (PHPivot::isSystemField($key)) continue;
            array_push($values, $val);
        }
        return $values;
    }

    //Counts number of children columns
    protected static function countChildrenCols($array, $_source_is_2DTable = false)
    {
        $children = 0;
        if (!$_source_is_2DTable) {
            if (is_array($array) && isset($array['_type']) && $array['_type'] == PHPivot::TYPE_COL) {
                foreach ($array as $col_name => $col_value) {
                    if (PHPivot::isSystemField($col_name)) continue;
                    $children += PHPivot::countChildrenCols($col_value);
                }
            }
            if ($children == 0) { //count self for colspan, if no children
                $children = 1;
            }
        } else {
            return count(PHPivot::pivot_array_keys($array)) + 1;
        }
        return $children;
    }

    //Generates the html code for columns
    protected function getColHtml(&$colpoint, $row_space, $coldepth = 0, $isLeftmost = true)
    {
        $html = '';
        if (is_array($colpoint) && count($this->_columns) - $coldepth > 0) {
            $new_html = '';
            $willBeLeftmost = true;
            foreach ($colpoint as $col_name => $col_value) {
                if (PHPivot::isSystemField($col_name)) continue;
                $new_html .= $this->getColHtml($col_value, $row_space, $coldepth + 1, $willBeLeftmost);
                $willBeLeftmost = false;
                $html .= '<th colspan="' . $this->countChildrenCols($col_value) . '">' . $this->escapeHtml($col_name) . '</th>';
            }
            if (count($this->_values) - $coldepth > 0) {
                $html = str_repeat($html, count($this->_values) - $coldepth);
            }
            if ($coldepth == 0) {
                return '<tr>' . $row_space . $html . '</tr>' . $new_html;
            } else {
                return ($isLeftmost ? $row_space : '') . $html . $new_html;
            }
        } else {
            return '';
        }
    }

    //Generates the html code to display the pivot table as an HTML table
    public function toHtml()
    {
        $row_space = '';
        for ($i = 0; $i < count($this->_rows); $i++) {
            $row_space .= '<th></th>';
        }

        $html_cols = '';
        //Print Column Values (final level)
        $colpoint = isset(PHPivot::pivot_array_values($this->_table)[0]) ? PHPivot::pivot_array_values($this->_table)[0] : null;
        $rowDepth = 1;
        while (!is_null($colpoint) && count($this->_rows) - $rowDepth > 0) {
            $colpoint = isset(PHPivot::pivot_array_values($colpoint)[0]) ? PHPivot::pivot_array_values($colpoint)[0] : null;
            $rowDepth++;
        }
        $html_cols = $this->getColHtml($colpoint, $row_space);
        $colwidth = $this->countChildrenCols($colpoint, $this->_source_is_2DTable); //@todo (pointer is missing now!) //@todo not sure about multi-val!

        $top_col_title_html =  '<th colspan="' . $colwidth . '">(No title)</th>';
        if (isset($this->_columns_titles[0])) {
            $top_col_title_html = '<th colspan="' . $colwidth . '">' . $this->escapeHtml($this->_columns_titles[0]) . '</th>';
        }

        //If multi-values, use multiple column titles (for additional values)
        if (count($this->_values) > 1) {
            for ($i = 1; $i < count($this->_columns_titles); $i++) {
                $top_col_title_html .=  '<th colspan="' . $colwidth . '">' . $this->escapeHtml($this->_columns_titles[$i]) . '</th>';
            }
        }

        $html_row_titles = '<tr>';
        for ($i = 0; $i < count($this->_rows_titles); $i++) {
            $html_row_titles .= '<th class="row_title">' . $this->escapeHtml($this->_rows_titles[$i]) . '</th>';
        }
        $html_row_titles .= '</tr>';

        $html = '<table><thead><tr>' . $row_space
            . $top_col_title_html . '</tr>' . $html_cols . $html_row_titles . '</thead>';


        //Print the data of the table
        foreach ($this->_table as $row_key => $row_data) {
            $html .= $this->htmlValues($row_key, $row_data, 0);
        }

        $html .= '</table>';
        return $html;
    }

    protected function getDataValue($row)
    {
        if (is_array($row) && (isset($row['_val']) || strcmp($row['_val'], '') == 0)) return $row['_val'];
        throw new \RuntimeException('PHPivot: Cannot find ["_val"] in data row: ' . print_r($row, true));
    }

    //Figures out where the actual value is and produces html code
    protected function htmlValues(&$key, &$row, $levels, $type = null)
    {
        $levelshtml = '';

        for ($i = 0; $i < $levels; $i++) {
            $levelshtml .= '<td></td>';
        }

        if (!PHPivot::isDataLevel($row)) {
            $html = '';
            if ($type == null || strcmp($type, PHPivot::TYPE_ROW) == 0) {
                $html .= '<td>' . $this->escapeHtml($key) . '</td>';
            }
            foreach ($row as $head => $nest) {
                if (PHPivot::isSystemField($head)) continue;
                $t = isset($row['_type']) ?  $row['_type'] : null;
                $new_row = $this->htmlValues($head, $nest, $levels + 1, $t);
                $html .=  $new_row;
            }
            if ($type == null || strcmp($type, PHPivot::TYPE_ROW) == 0) {
                $html = '<tr>' . $levelshtml . $html . '</tr>';
            }
            return $html;
        } else {
            if (isset($row['_type']) && strcmp($row['_type'], PHPivot::TYPE_COMP) == 0) { //Deepest level row, with comparison data
                $c = '<td>';
                for ($i = 0; $i < count($row['_val']); $i++) {
                    $c .=  $this->escapeHtml($row['_val'][$i]);
                    if ($i + 1 < count($row['_val'])) $c .= ' &rarr; ';
                }
                $c .= '</td>';
                return $c;
            } else if (isset($row['_type']) && strcmp($row['_type'], PHPivot::TYPE_VAL) == 0) { //Deepest level row, with value data
                return '<td>' . $this->escapeHtml($this->getDataValue($row)) . '</td>';
            } else if ($type == PHPivot::TYPE_ROW) { //Deepest level row
                $html = '<tr>' . $levelshtml . '<td>' . $this->escapeHtml($key) . '</td>';
                $html .= '<td style="background:' . $this->escapeHtml($this->getColorOf($row)) . ' !important">' . $this->escapeHtml($row) . '</td>';
                return $html . '</tr>';
            } else { //Deepest level column
                if ($levels == 0) {
                    if (PHPivot::isSystemField($key)) return '';
                    return '<tr><td>' . $this->escapeHtml($key) . '</td><td style="background:' . $this->escapeHtml($this->getColorOf($row)) . ' !important">' . $this->escapeHtml($row) . '</td></tr>';
                } else {
                    $inNest = ($levels - count($this->_columns) - count($this->_rows) + 1 > 0);
                    if (!$inNest) {
                        return '<td style="background:' . $this->escapeHtml($this->getColorOf($row)) . ' !important">' . $this->escapeHtml($row) . '</td>';
                    } else {
                        return  '<td>' . $this->escapeHtml($key) . '</td>' . '<td style="background:' . $this->escapeHtml($this->getColorOf($row)) . ' !important">' . $this->escapeHtml($row) . '</td>';
                    }
                }
            }
        }
    }
}
