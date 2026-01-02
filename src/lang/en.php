<?php

return [
    // Error messages
    'error.invalid_recordset' => 'Recordset must be an array',
    'error.invalid_filter_column' => 'Filter column must be a non-empty string',
    'error.invalid_compare_operator' => 'Invalid compare operator',
    'error.invalid_match_mode' => 'Invalid match mode',
    'error.invalid_sort_parameter' => 'Sort parameter must be SORT_ASC, SORT_DESC, or a callable',
    'error.invalid_color_format' => '{color} color must be in hex format #RRGGBB',
    'error.invalid_calculated_column' => 'Calculated Column function {function} is not callable.',
    'error.invalid_color_by' => 'Invalid colorBy parameter',
    'error.invalid_getcolor'=>'getColorOf not programmed to handle COLOR_BY={color}',
    'error.invalid_precision' => 'Decimal precision must be a non-negative integer',
    'error.color_not_implemented' => 'Color function not implemented for mode: {mode}',
    'error.filter_not_callable' => 'Filter function must be callable',
    'error.column_function_mismatch' => 'Column name and function count mismatch',
    'error.invalid_value_function' => 'Value function not recognized: {function}',
    'error.value_function_count_mismatch' => 'Value Fields and Function Count do not match.',
    'error.invalid_display_mode' => 'Cannot format data as: {mode}',
    'error.invalid_data_structure' => 'Cannot find ["_val"] in data row (invalid data structure)',
    'error.invalid_filter' => 'Invalid filter, must implement FilterInterface',

];