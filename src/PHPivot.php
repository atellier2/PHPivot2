<?php

namespace Atellier2\PHPivot;

use Atellier2\PHPivot\Utils\Utils;
use Atellier2\PHPivot\Utils\ArrayUtils;
use Atellier2\PHPivot\Utils\ColorUtils;
use Atellier2\PHPivot\Utils\ValueUtils;
use Atellier2\PHPivot\Config\PivotConstants;
use Atellier2\PHPivot\Exception\PHPivotException;
use Atellier2\PHPivot\Service\Filter\CustomFilter;
use Atellier2\PHPivot\Service\Filter\FilterInterface;
use Atellier2\PHPivot\Service\Filter\ComparisonFilter;

class PHPivot
{
    public static array $SYSTEM_FIELDS = PivotConstants::SYSTEM_FIELDS;

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
    protected $_columns_sort = PivotConstants::SORT_ASC;
    protected $_rows = array();
    protected $_rows_titles = array();

    /* array of booleans indicating whether to show sum for each row level */
    protected $_rows_sum = array();

    /**
     * @var int|array|callable Sort order for rows (SORT_ASC, SORT_DESC, or callable)
     * 
     * @see setSortRows()
     */
    protected $_rows_sort = PivotConstants::SORT_ASC;

    /**
     * @var bool Whether to ignore blank values in the pivot table
     */
    protected $_ignore_blanks = false;

    /**
     * @var int Color coding mode (e.g., COLOR_ALL, COLOR_ROWS, COLOR_COLUMNS)
     */
    protected $_color_by = PivotConstants::COLOR_ALL;
    protected $_color_low = null;
    protected $_color_high = null;
    protected $_color_of = array();

    protected $_cache_rows_unique_values = array();
    protected $_cache_columns_unique_values = array();

    protected $_filters = array();

    protected $_source_is_2DTable = false;



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

        $array_vals = array_keys($pivotTable[$array_rows[0]]);

        foreach ($pivotTable as $rowName => $rowContent) {
            $pivotTable[$rowName]['_type'] = PivotConstants::TYPE_COL;
            foreach ($rowContent as $valueName => $value) {
                $pivotTable[$rowName][$valueName] = array('_type' => PivotConstants::TYPE_VAL, '_val' => $value);
            }
        }
        $pivotTable['_type'] = PivotConstants::TYPE_ROW;

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

        $pivotTable['_type'] = PivotConstants::TYPE_ROW;

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
     * @throws PHPivotException if recordset is not an array
     */
    public function __construct($recordset)
    {
        if (!is_array($recordset)) {
            throw new PHPivotException(__('error.invalid_recordset'));
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
     * define the value fields, their functions and display mode
     * 
     * @param array|string $values The value fields
     * @param array|int $functions The functions to apply to each value field
     * @param int $display The display mode (see DISPLAY_AS_ constants)
     * @param array|string $titles The titles of the value fields (optional)
     */
    public function setPivotValueFields($values, $functions = PivotConstants::PIVOT_VALUE_SUM, /*only 1*/ $display = PivotConstants::DISPLAY_AS_VALUE, $titles = null)
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
                throw new PHPivotException(__('error.value_function_count_mismatch'), PHPivotException::INVALID_CONFIGURATION);
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

    /**
     * define how to display the values
     * 
     * data could be displayed as: value, percentage of column, percentage of row, percentage of deepest level, etc.
     * 
     * @param int $display The display mode (see DISPLAY_AS_ constants)
     */
    public function setDisplayAs($display = PivotConstants::DISPLAY_AS_VALUE)
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
     * @throws PHPivotException if precision is negative
     */
    public function setDecimalPrecision($precision)
    {
        if (!is_int($precision) || $precision < 0) {
            throw new PHPivotException(__('error.invalid_precision'));
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
     * @throws PHPivotException if color format is invalid
     */
    public function setColorRange($low = '#00af5d', $high = '#ff0017', $colorBy = null)
    {
        // Validate hex color format
        if (!ColorUtils::isValidHexColor($low)) {
            throw new PHPivotException(__('error.invalid_color_format',['%color%' => 'low']));
        }
        if (!ColorUtils::isValidHexColor($high)) {
            throw new PHPivotException(__('error.invalid_color_format',['%color%' => 'high']));
        }

        if (is_null($colorBy)) {
            $colorBy = PivotConstants::COLOR_ALL;
        }

        if (!in_array($colorBy, [PivotConstants::COLOR_ALL, PivotConstants::COLOR_BY_ROW, PivotConstants::COLOR_BY_COL], true)) {
            throw new PHPivotException(__('error.invalid_color_by'));
        }

        $this->_color_by = $colorBy;
        $this->_color_low = $low;
        $this->_color_high = $high;

        return $this;
    }

    /**
     * In case we have no data, we could omit it if flag set
     * 
     * @param array|null $point The current point in the pivot table
     * @return int|bool The count of non-blank values or false if blanks are not ignored
     */
    protected function cleanBlanks(?array &$point = null):int|bool
    {
        if (!$this->_ignore_blanks) return 0;

        $countNonBlank = 0;
        if (PHPivot::isDataLevel($point)) {
            if (!is_array($point)) {
                return (!is_null($point) && !empty($point) ? 1 : 0);
            } else if (in_array($point['_type'], [PivotConstants::TYPE_COMP, PivotConstants::TYPE_VAL], true)) {
                $data_values = ArrayUtils::getPivotValues($point);
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
            if (ArrayUtils::isSystemField($point_keys[$i])) continue;

            if ($this->cleanBlanks($point[$point_keys[$i]]) > 0) {
                $countNonBlank++;
            } else if (isset($point['_type']) && $point['_type'] === PivotConstants::TYPE_ROW) {
                unset($point[$point_keys[$i]]);
            }
        }

        return $countNonBlank;
    }

    /**
     * Validate a single sort value
     * 
     * @param mixed $sort The sort value to validate
     * @return bool True if valid
     */
    private function isValidSortValue($sort)
    {
        return is_callable($sort) || in_array($sort, [PivotConstants::SORT_ASC, PivotConstants::SORT_DESC], true);
    }

    /**
     * Validate sort parameter
     * 
     * @param int|array|callable $sortby The sort parameter to validate
     * @throws PHPivotException if sort parameter is invalid
     */
    private function validateSortParameter($sortby)
    {
        if (is_array($sortby)) {
            foreach ($sortby as $sort) {
                if (!$this->isValidSortValue($sort)) {
                    throw new PHPivotException(__('error.invalid_sort_parameter'));
                }
            }
        } else if (!$this->isValidSortValue($sortby)) {
            throw new PHPivotException(__('error.invalid_sort_parameter'));
        }
    }

    /**
     * Set the sorting order for columns
     * 
     * @param int|array|callable $sortby Sort order (SORT_ASC, SORT_DESC) or array of sort orders or callable
     * @return $this
     * @throws PHPivotException if sort parameter is invalid
     */
    public function setSortColumns($sortby)
    {
        $this->validateSortParameter($sortby);
        $this->_columns_sort = $sortby;
        return $this;
    }

    /**
     * Set the sorting order for rows
     * 
     * @param int|array|callable $sortby Sort order (SORT_ASC, SORT_DESC) or array of sort orders or callable
     * @return $this
     * @throws PHPivotException if sort parameter is invalid
     */
    public function setSortRows($sortby)
    {
        $this->validateSortParameter($sortby);
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
            throw new PHPivotException(__('error.column_function_mismatch'));
        }
        for ($i = 0; $i < count($col_name); $i++) {
            $calc_col = array();
            $calc_col['name'] = $col_name[$i];

            if (!is_callable($calc_function[$i])) {
                throw new PHPivotException(__('error.invalid_calculated_column', ['function' => $calc_function[$i]]));
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
     * @throws PHPivotException if parameters are invalid
     */
    public function addFilter($column, $value, $compare = PivotConstants::COMPARE_EQUAL, $match = PivotConstants::FILTER_MATCH_ALL)
    {
        $this->_filters[] = new ComparisonFilter($column, $value, $compare, $match);
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
     * @throws PHPivotException if the function is not callable
     */
    public function addCustomFilter($filterFn, $extra_params = null)
    {
        $this->_filters[] = new CustomFilter($filterFn, $extra_params);
        return $this;
    }

    //pass data through filters and see if it's a match
    private function isFilterOK($rs_row): bool
    {
        foreach ($this->_filters as $filter) {
            if (!$filter instanceof FilterInterface) {
                throw new PHPivotException(__('error.invalid_filter'), PHPivotException::INVALID_CONFIGURATION);
            }
            if (!$filter->matches($rs_row)) {
                return false;
            }
        }
        return true;
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
    public function generate():self
    {
        $table = array();

        if (empty($this->_recordset)) {
            $this->_table = $table;
            return $this;
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
                    $value = $rs_row[$col] ?? null;
                    if (!in_array($value, $columns_unique_values[$col], true)) {
                        $columns_unique_values[$col][] = $value;
                    }
                }
                foreach ($this->_rows as $row_title) {
                    $value = $rs_row[$row_title] ?? null;
                    if (!in_array($value, $rows_unique_values[$row_title], true)) {
                        $rows_unique_values[$row_title][] = $value;
                    }
                }
            }

            //Sort columns and rows names
            foreach ($this->_columns as $index => $col) {
                $sort = $this->_columns_sort;
                if (is_array($this->_columns_sort)) {
                    $sort = $this->_columns_sort[$index] ?? PivotConstants::SORT_ASC;
                }
                if ($sort == PivotConstants::SORT_ASC || $sort == PivotConstants::SORT_DESC) {
                    natsort($columns_unique_values[$col]);
                    if ($sort == PivotConstants::SORT_DESC) {
                        $columns_unique_values[$col] = array_reverse($columns_unique_values[$col]);
                    }
                } else {
                    usort($columns_unique_values[$col], $sort);
                }
            }
            foreach ($this->_rows as $index => $row) {
                $sort = $this->_rows_sort;
                if (is_array($this->_rows_sort)) {
                    $sort = $this->_rows_sort[$index] ?? PivotConstants::SORT_ASC;
                }
                if ($sort == PivotConstants::SORT_ASC || $sort == PivotConstants::SORT_DESC) {
                    natsort($rows_unique_values[$row]);
                    if ($sort == PivotConstants::SORT_DESC) {
                        $rows_unique_values[$row] = array_reverse($rows_unique_values[$row]);
                    }
                } else {
                    usort($rows_unique_values[$row], $sort);
                }
            }

            //Create an associative array with all the value fields (for all rows)
            $values_assoc = array();
            for ($i = 0; $i < count($this->_values); $i++) {
                $new_values_assoc = array();
                $new_values_assoc['_type'] = PivotConstants::TYPE_VAL;
                //$new_values_assoc['_title'] = $this->_values[$i]; // not needed anymore
                $new_values_assoc['_val'] = null;
                $values_assoc[$this->_values[$i]] = $new_values_assoc;
            }

            //Create an associative array with all the unique values for all the columns
            $columns_assoc = $values_assoc;
            for ($i = count($this->_columns) - 1; $i >= 0; $i--) {
                $new_columns_assoc = array();
                $new_columns_assoc['_type'] = PivotConstants::TYPE_COL;

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
                $new_rows_assoc['_type'] = PivotConstants::TYPE_ROW;
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
                    $point['_type'] = PivotConstants::TYPE_VAL; //make sure we "label" this as a value level array (needed for "no columns" cases)
                    $value_function = $this->_values_functions[$val_ind];

                    switch ($value_function) {
                        case PivotConstants::PIVOT_VALUE_COUNT:
                            if (is_null($value_point)) {
                                $value_point = 1;
                            } else {
                                $value_point = $value_point + 1;
                            }
                            break;

                        case PivotConstants::PIVOT_VALUE_SUM:
                            if (is_null($value_point) && !is_null($rs_row[$val])) {
                                $value_point = $rs_row[$val];
                            } else {
                                $value_point += $rs_row[$val];
                            }
                            break;

                        default:
                            throw new PHPivotException(__('error.invalid_value_function', ['function' => $value_function]), PHPivotException::INVALID_CONFIGURATION);
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



    protected static function isDeepestLevel(&$row)
    {
        foreach ($row as $key => $child) {
            if (ArrayUtils::isSystemField($key)) continue;
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
            ($row['_type'] === PivotConstants::TYPE_VAL ||
                $row['_type'] === PivotConstants::TYPE_COMP));
    }





    private function findMax(&$row, $findMax = true)
    {
        if (PHPivot::isDataLevel($row)) {
            $v = ArrayUtils::getPivotValues($row);
            $find = null;
            if (empty($v)) return null;

            if (is_array($v)) {
                $find = ValueUtils::getValueFromFormat($v[0], $this->_values_display);
                for ($i = 1; $i < count($v); $i++) {
                    $find = ValueUtils::getEdgeValue($find, ValueUtils::getValueFromFormat($v[$i], $this->_values_display), $findMax);
                }
            } else {
                $find = ValueUtils::getValueFromFormat($v, $this->_values_display);
            }
            return $find;
        } else {
            $find = null;
            $k = ArrayUtils::getPivotKeys($row);
            for ($i = 0; $i < count($k); $i++) {
                $find = ValueUtils::getEdgeValue($find, PHPivot::findMax($row[$k[$i]], $findMax), $findMax);
            }
            return $find;
        }
    }

    private function findMin(&$row)
    {
        return $this->findMax($row, false);
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
            case PivotConstants::COLOR_ALL:
                $v = ValueUtils::getValueFromFormat($value, $this->_values_display);
                if (isset($this->_color_of[$v]))
                    return $this->_color_of[$v];
                else
                    return 'inherit';
                break;
            default:
                throw new PHPivotException(__('error.invalid_getcolor', ['color' => $this->_color_by]), PHPivotException::INVALID_CONFIGURATION);
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
            case PivotConstants::COLOR_ALL:
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
                $fromColor = ColorUtils::hexToRGB($this->_color_low);
                $toColor = ColorUtils::hexToRGB($this->_color_high);
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
            case PivotConstants::COLOR_BY_ROW:
                //@todo
                throw new PHPivotException(__('error.color_not_implemented', ['mode' => PivotConstants::COLOR_BY_ROW]), PHPivotException::INVALID_CONFIGURATION);
                break;
            case PivotConstants::COLOR_BY_COL:
                //@todo
                throw new PHPivotException(__('error.color_not_implemented', ['mode' => PivotConstants::COLOR_BY_COL]), PHPivotException::INVALID_CONFIGURATION);
                break;
            default:
                throw new PHPivotException(__('error.color_not_implemented', ['mode' => $this->_color_by]), PHPivotException::INVALID_CONFIGURATION);
                break;
        }
    }



    /**
     * Calculates the percentage out of sum given, sets the value (or appends)
     * making the _val field "23%" or "3 (23%)"
     * 
     * @param array $d The data array to process
     * @param float|int $sum The sum to calculate percentage from
     * @param bool $keepValue Whether to keep the original value
     */
    private function setAsPercOf(&$d, $sum, $keepValue = false)
    {
        if (!is_array($d)) return;
        // Return early to avoid division by zero
        if ($sum == 0) return;

        if (array_key_exists('_val', $d)) {
            $actual_value = $d['_val'];
            if (empty($actual_value)) {
                $actual_value = 0;
            }

            // Calculate percentage - this is where division by zero would occur without the check above
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

    /**
    * Formats the values as requested in class variable "_values_display" (e.g. % by column)
    */
    private function formatData(&$row)
    {
        switch ($this->_values_display) {
            case PivotConstants::DISPLAY_AS_VALUE:
                return;
                break;

            case PivotConstants::DISPLAY_AS_VALUE_AND_PERC_ROW:
            case PivotConstants::DISPLAY_AS_PERC_ROW:
                //Empty table
                if (!is_array($row)) return;

                //BFS and reach the deepest row
                if (!empty(($row)) && array_key_exists('_type', $row) && $row['_type'] === PivotConstants::TYPE_ROW) {
                    $keys = array_keys($row);
                    $keycount = count($keys);
                    for ($i = 0; $i < $keycount; $i++) {
                        $this->formatData($row[$keys[$i]]);
                    }
                    return;
                }

                //We are at columns level:
                //Sum up all VALUES
                $sum = ValueUtils::getSumOf($row);

                $keepValue = false;
                switch ($this->_values_display) {
                    case PivotConstants::DISPLAY_AS_VALUE_AND_PERC_ROW:
                        $keepValue = true;
                        break;
                }

                //Calculate % of sum for each value:
                $this->setAsPercOf($row, $sum, $keepValue);
                break;


            case PivotConstants::DISPLAY_AS_VALUE_AND_PERC_COL:
            case PivotConstants::DISPLAY_AS_PERC_COL:
                //Empty table
                if (!is_array($row)) return;

                //BFS and reach the deepest COL
                if (!empty(($row)) && array_key_exists('_type', $row) && ($row['_type']=== PivotConstants::TYPE_COL) == 0) {
                    $keys = array_keys($row);
                    $keycount = count($keys);
                    for ($i = 0; $i < $keycount; $i++) {
                        $this->formatData($row[$keys[$i]]);
                    }
                    return;
                }

                //We are at columns level:
                //Sum up all VALUES
                $sum = ValueUtils::getSumOf($row);

                $keepValue = false;
                switch ($this->_values_display) {
                    case PivotConstants::DISPLAY_AS_VALUE_AND_PERC_COL:
                        $keepValue = true;
                        break;
                }

                //Calculate % of sum for each value:
                $this->setAsPercOf($row, $sum, $keepValue);

                break;

            // TODO: Re-implement DISPLAY_AS_PERC_DEEPEST_LEVEL feature
            case PivotConstants::DISPLAY_AS_PERC_DEEPEST_LEVEL:
            case PivotConstants::DISPLAY_AS_VALUE_AND_PERC_DEEPEST_LEVEL:
                // Note: DISPLAY_AS_PERC_DEEPEST_LEVEL needs re-implementation. Displaying plain values.
                break;
            default:
                throw new PHPivotException('PHPivot: Cannot format data as: ' . $this->_values_display, PHPivotException::INVALID_CONFIGURATION);
                break;
        }
    }

    public function toArray():array
    {
        return $this->_table;
    }

    public function toRawArray()
    {
        return $this->_raw_table;
    }

    //Counts number of children columns
    protected static function countChildrenCols($array, $_source_is_2DTable = false)
    {
        $children = 0;
        if (!$_source_is_2DTable) {
            if (is_array($array) && isset($array['_type']) && $array['_type'] == PivotConstants::TYPE_COL) {
                foreach ($array as $col_name => $col_value) {
                    if (ArrayUtils::isSystemField($col_name)) continue;
                    $children += PHPivot::countChildrenCols($col_value);
                }
            }
            if ($children == 0) { //count self for colspan, if no children
                $children = 1;
            }
        } else {
            return count(ArrayUtils::getPivotKeys($array)) + 1;
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
                if (ArrayUtils::isSystemField($col_name)) continue;
                $new_html .= $this->getColHtml($col_value, $row_space, $coldepth + 1, $willBeLeftmost);
                $willBeLeftmost = false;
                $html .= '<th colspan="' . $this->countChildrenCols($col_value) . '">' . Utils::escapeHtml($col_name) . '</th>';
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
        $colpoint = isset(ArrayUtils::getPivotValues($this->_table)[0]) ? ArrayUtils::getPivotValues($this->_table)[0] : null;
        $rowDepth = 1;
        while (!is_null($colpoint) && count($this->_rows) - $rowDepth > 0) {
            $colpoint = isset(ArrayUtils::getPivotValues($colpoint)[0]) ? ArrayUtils::getPivotValues($colpoint)[0] : null;
            $rowDepth++;
        }
        $html_cols = $this->getColHtml($colpoint, $row_space);
        $colwidth = $this->countChildrenCols($colpoint, $this->_source_is_2DTable); //@todo (pointer is missing now!) //@todo not sure about multi-val!

        $top_col_title_html =  '<th colspan="' . $colwidth . '">(No title)</th>';
        if (isset($this->_columns_titles[0])) {
            $top_col_title_html = '<th colspan="' . $colwidth . '">' . Utils::escapeHtml($this->_columns_titles[0]) . '</th>';
        }

        //If multi-values, use multiple column titles (for additional values)
        if (count($this->_values) > 1) {
            for ($i = 1; $i < count($this->_columns_titles); $i++) {
                $top_col_title_html .=  '<th colspan="' . $colwidth . '">' . Utils::escapeHtml($this->_columns_titles[$i]) . '</th>';
            }
        }

        $html_row_titles = '<tr>';
        for ($i = 0; $i < count($this->_rows_titles); $i++) {
            $html_row_titles .= '<th class="row_title">' . Utils::escapeHtml($this->_rows_titles[$i]) . '</th>';
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
        if (is_array($row) && (array_key_exists('_val', $row))) return $row['_val']??0;
        
        throw new PHPivotException('PHPivot: Cannot find ["_val"] in data row (invalid data structure)', PHPivotException::INVALID_CONFIGURATION);
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
            if ($type == null || $type === PivotConstants::TYPE_ROW) {
                $html .= '<td>' . Utils::escapeHtml($key) . '</td>';
            }
            foreach ($row as $head => $nest) {
                if (ArrayUtils::isSystemField($head)) continue;
                $t = isset($row['_type']) ?  $row['_type'] : null;
                $new_row = $this->htmlValues($head, $nest, $levels + 1, $t);
                $html .=  $new_row;
            }
            if ($type == null || $type === PivotConstants::TYPE_ROW) {
                $html = '<tr>' . $levelshtml . $html . '</tr>';
            }
            return $html;
        } else {
            if (isset($row['_type']) && $row['_type'] === PivotConstants::TYPE_COMP) { //Deepest level row, with comparison data
                $c = '<td>';
                for ($i = 0; $i < count($row['_val']); $i++) {
                    $c .=  Utils::escapeHtml($row['_val'][$i]);
                    if ($i + 1 < count($row['_val'])) $c .= ' &rarr; ';
                }
                $c .= '</td>';
                return $c;
            } else if (isset($row['_type']) && $row['_type'] === PivotConstants::TYPE_VAL) { //Deepest level row, with value data
                return '<td>' . Utils::escapeHtml($this->getDataValue($row)) . '</td>';
            } else if ($type == PivotConstants::TYPE_ROW) { //Deepest level row
                $html = '<tr>' . $levelshtml . '<td>' . Utils::escapeHtml($key) . '</td>';
                $html .= '<td style="background:' . Utils::escapeHtml($this->getColorOf($row)) . ' !important">' . Utils::escapeHtml($row) . '</td>';
                return $html . '</tr>';
            } else { //Deepest level column
                if ($levels == 0) {
                    if (ArrayUtils::isSystemField($key)) return '';
                    return '<tr><td>' . Utils::escapeHtml($key) . '</td><td style="background:' . Utils::escapeHtml($this->getColorOf($row)) . ' !important">' . Utils::escapeHtml($row) . '</td></tr>';
                } else {
                    $inNest = ($levels - count($this->_columns) - count($this->_rows) + 1 > 0);
                    if (!$inNest) {
                        return '<td style="background:' . Utils::escapeHtml($this->getColorOf($row)) . ' !important">' . Utils::escapeHtml($row) . '</td>';
                    } else {
                        return  '<td>' . Utils::escapeHtml($key) . '</td>' . '<td style="background:' . Utils::escapeHtml($this->getColorOf($row)) . ' !important">' . Utils::escapeHtml($row) . '</td>';
                    }
                }
            }
        }
    }
}
