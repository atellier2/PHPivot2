<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;

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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter column must be a non-empty string');
        
        PHPivot::create($this->validData)
            ->addFilter('', 'value');
    }
    
    /**
     * Test that addFilter validates compare operator
     */
    public function testAddFilterThrowsExceptionForInvalidCompareOperator()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid compare operator');
        
        PHPivot::create($this->validData)
            ->addFilter('name', 'John', 999);
    }
    
    /**
     * Test that addFilter validates match mode
     */
    public function testAddFilterThrowsExceptionForInvalidMatchMode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid match mode');
        
        PHPivot::create($this->validData)
            ->addFilter('name', 'John', PHPivot::COMPARE_EQUAL, 999);
    }
    
    /**
     * Test that addCustomFilter validates callable
     */
    public function testAddCustomFilterThrowsExceptionForNonCallable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter function must be callable');
        
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not callable');
        
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('column name and function count mismatch');
        
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sort parameter must be SORT_ASC, SORT_DESC, or a callable');
        
        PHPivot::create($this->validData)
            ->setSortColumns(999);
    }
    
    /**
     * Test that setSortColumns accepts valid constants
     */
    public function testSetSortColumnsAcceptsValidConstants()
    {
        $pivot = PHPivot::create($this->validData)
            ->setSortColumns(PHPivot::SORT_ASC);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setSortColumns(PHPivot::SORT_DESC);
        
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
            ->setSortColumns([PHPivot::SORT_ASC, PHPivot::SORT_DESC]);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that setSortColumns throws exception for invalid array element
     */
    public function testSetSortColumnsThrowsExceptionForInvalidArrayElement()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort value in array');
        
        PHPivot::create($this->validData)
            ->setSortColumns([PHPivot::SORT_ASC, 999]);
    }
    
    /**
     * Test that setSortRows validates sort parameter
     */
    public function testSetSortRowsThrowsExceptionForInvalidSort()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sort parameter must be SORT_ASC, SORT_DESC, or a callable');
        
        PHPivot::create($this->validData)
            ->setSortRows(999);
    }
    
    /**
     * Test that setSortRows accepts valid constants
     */
    public function testSetSortRowsAcceptsValidConstants()
    {
        $pivot = PHPivot::create($this->validData)
            ->setSortRows(PHPivot::SORT_ASC);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setSortRows(PHPivot::SORT_DESC);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that setColorRange validates hex color format
     */
    public function testSetColorRangeThrowsExceptionForInvalidLowColor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Low color must be in hex format #RRGGBB');
        
        PHPivot::create($this->validData)
            ->setColorRange('red', '#ff0000');
    }
    
    /**
     * Test that setColorRange validates hex color format for high color
     */
    public function testSetColorRangeThrowsExceptionForInvalidHighColor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('High color must be in hex format #RRGGBB');
        
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid colorBy parameter');
        
        PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', 999);
    }
    
    /**
     * Test that setColorRange accepts valid colorBy constants
     */
    public function testSetColorRangeAcceptsValidColorByConstants()
    {
        $pivot = PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', PHPivot::COLOR_ALL);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', PHPivot::COLOR_BY_ROW);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        
        $pivot = PHPivot::create($this->validData)
            ->setColorRange('#00ff00', '#ff0000', PHPivot::COLOR_BY_COL);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
}
