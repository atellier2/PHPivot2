<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;
use Atellier2\PHPivot\Config\PivotConstants;
use Atellier2\PHPivot\Exception\PHPivotException;

/**
 * Test input validation and exception handling
 * 
 * These tests verify that the library properly validates inputs
 * and throws appropriate exceptions for invalid data.
 */
class InputValidationTest extends TestCase
{
    private $validData;
    
    protected function setUp(): void
    {
        $this->validData = [
            ['name' => 'John', 'age' => 30, 'amount' => 100],
            ['name' => 'Jane', 'age' => 25, 'amount' => 200]
        ];
    }
    
    /**
     * Test that addFilter validates column parameter
     */
    public function testAddFilterThrowsExceptionForEmptyColumn()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_filter_column'));
        
        PHPivot::create($this->validData)
            ->addFilter('', 'value');
    }
    
    /**
     * Test that addFilter validates compare operator
     */
    public function testAddFilterThrowsExceptionForInvalidCompareOperator()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_compare_operator'));
        
        PHPivot::create($this->validData)
            ->addFilter('name', 'John', 999);
    }
    
    /**
     * Test that addFilter validates match mode
     */
    public function testAddFilterThrowsExceptionForInvalidMatchMode()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_match_mode'));
        
        PHPivot::create($this->validData)
            ->addFilter('name', 'John', PivotConstants::COMPARE_EQUAL, 999);
    }
    
    /**
     * Test that addCustomFilter validates callable
     */
    public function testAddCustomFilterThrowsExceptionForNonCallable()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_filter_function'));
        
        PHPivot::create($this->validData)
            ->addCustomFilter('not_a_function');
    }
    
    /**
     * Test that addCustomFilter accepts valid callable
     */
    public function testAddCustomFilterAcceptsValidCallable()
    {
        $pivot = PHPivot::create($this->validData)
            ->addCustomFilter(function($recordset, $rowID, $params) {
                return true;
            });
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that addCalculatedColumns validates callable
     */
    public function testAddCalculatedColumnsThrowsExceptionForNonCallable()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__(__('error.invalid_calculated_column', ['function' => 'not_a_function'])));
        
        PHPivot::create($this->validData)
            ->addCalculatedColumns('new_col', 'not_a_function');
    }
    
    /**
     * Test that addCalculatedColumns accepts valid callable
     */
    public function testAddCalculatedColumnsAcceptsValidCallable()
    {
        $pivot = PHPivot::create($this->validData)
            ->addCalculatedColumns('new_col', function($recordset, $rowID) {
                return $recordset[$rowID]['amount'] * 2;
            });
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that addCalculatedColumns validates array counts match
     */
    public function testAddCalculatedColumnsThrowsExceptionForMismatchedArrays()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.column_function_mismatch'));
        
        PHPivot::create($this->validData)
            ->addCalculatedColumns(
                ['col1', 'col2'],
                [function($rs, $id) { return 1; }]
            );
    }
    
    /**
     * Test that setSortColumns validates sort parameter
     */
    public function testSetSortColumnsThrowsExceptionForInvalidSort()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_sort_parameter'));
        
        PHPivot::create($this->validData)
            ->setSortColumns(999);
    }
    
    /**
     * Test that setSortColumns accepts valid constants
     */
    public function testSetSortColumnsAcceptsValidConstants()
    {
        $pivot = PHPivot::create($this->validData)
            ->setSortColumns(PivotConstants::SORT_ASC);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setSortColumns(PivotConstants::SORT_DESC);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that setSortColumns accepts callable
     */
    public function testSetSortColumnsAcceptsCallable()
    {
        $pivot = PHPivot::create($this->validData)
            ->setSortColumns(function($a, $b) {
                return $a < $b;
            });
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that setSortColumns accepts array of sort values
     */
    public function testSetSortColumnsAcceptsArrayOfSortValues()
    {
        $pivot = PHPivot::create($this->validData)
            ->setSortColumns([PivotConstants::SORT_ASC, PivotConstants::SORT_DESC]);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that setSortColumns throws exception for invalid array element
     */
    public function testSetSortColumnsThrowsExceptionForInvalidArrayElement()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_sort_parameter'));
        
        PHPivot::create($this->validData)
            ->setSortColumns([PivotConstants::SORT_ASC, 999]);
    }
    
    /**
     * Test that setSortRows validates sort parameter
     */
    public function testSetSortRowsThrowsExceptionForInvalidSort()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_sort_parameter'));
        
        PHPivot::create($this->validData)
            ->setSortRows(999);
    }
    
    /**
     * Test that setSortRows accepts valid constants
     */
    public function testSetSortRowsAcceptsValidConstants()
    {
        $pivot = PHPivot::create($this->validData)
            ->setSortRows(PivotConstants::SORT_ASC);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setSortRows(PivotConstants::SORT_DESC);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that setColorRange validates hex color format
     */
    public function testSetColorRangeThrowsExceptionForInvalidLowColor()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_color_format', ['%color%' => 'Low']));
        
        PHPivot::create($this->validData)
            ->setColorRange('red', '#ff0000');
    }
    
    /**
     * Test that setColorRange validates hex color format for high color
     */
    public function testSetColorRangeThrowsExceptionForInvalidHighColor()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_color_format', ['%color%' => 'High']));
        
        PHPivot::create($this->validData)
            ->setColorRange('#00ff00', 'blue');
    }
    
    /**
     * Test that setColorRange accepts valid hex colors
     */
    public function testSetColorRangeAcceptsValidHexColors()
    {
        $pivot = PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000');
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that setColorRange validates colorBy parameter
     */
    public function testSetColorRangeThrowsExceptionForInvalidColorBy()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_color_by'));
        
        PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', 999);
    }
    
    /**
     * Test that setColorRange accepts valid colorBy constants
     */
    public function testSetColorRangeAcceptsValidColorByConstants()
    {
        $pivot = PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', PivotConstants::COLOR_ALL);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', PivotConstants::COLOR_BY_ROW);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', PivotConstants::COLOR_BY_COL);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
}
