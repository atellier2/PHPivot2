<?php

namespace Atellier2\PHPivot\Tests\Integration;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;
use Atellier2\PHPivot\Config\PivotConstants;

/**
 * Test edge cases and boundary conditions
 * 
 * These tests verify that the library handles edge cases correctly.
 */
class EdgeCasesTest extends TestCase
{
    /**
     * Test with single data point
     */
    public function testSingleDataPoint()
    {
        $data = [
            ['name' => 'John', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertArrayHasKey('John', $table);
        $this->assertEquals(100, $table['John']['amount']['_val']);
    }
    
    /**
     * Test with very large numbers
     */
    public function testLargeNumbers()
    {
        $data = [
            ['name' => 'John', 'amount' => 9999999999]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertEquals(9999999999, $table['John']['amount']['_val']);
    }
    
    /**
     * Test with negative numbers
     */
    public function testNegativeNumbers()
    {
        $data = [
            ['name' => 'John', 'amount' => -100],
            ['name' => 'Jane', 'amount' => -200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertEquals(-100, $table['John']['amount']['_val']);
        $this->assertEquals(-200, $table['Jane']['amount']['_val']);
    }
    
    /**
     * Test with decimal numbers
     */
    public function testDecimalNumbers()
    {
        $data = [
            ['name' => 'John', 'amount' => 123.45],
            ['name' => 'Jane', 'amount' => 678.90]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertEquals(123.45, $table['John']['amount']['_val']);
        $this->assertEquals(678.90, $table['Jane']['amount']['_val']);
    }
    
    /**
     * Test with zero values
     */
    public function testZeroValues()
    {
        $data = [
            ['name' => 'John', 'amount' => 0],
            ['name' => 'Jane', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertEquals(0, $table['John']['amount']['_val']);
        $this->assertEquals(100, $table['Jane']['amount']['_val']);
    }
    
    /**
     * Test with long strings
     */
    public function testLongStrings()
    {
        $longString = str_repeat('A', 1000);
        
        $data = [
            ['name' => $longString, 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertArrayHasKey($longString, $table);
        $this->assertEquals(100, $table[$longString]['amount']['_val']);
    }
    
    /**
     * Test with special characters in field names
     */
    public function testSpecialCharactersInFieldNames()
    {
        $data = [
            ['name-with-dash' => 'John', 'amount$' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name-with-dash')
            ->setPivotValueFields('amount$', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertArrayHasKey('John', $table);
    }
    
    /**
     * Test with duplicate row names
     */
    public function testDuplicateRowNames()
    {
        $data = [
            ['name' => 'John', 'amount' => 100],
            ['name' => 'John', 'amount' => 200],
            ['name' => 'John', 'amount' => 150]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should sum all Johns: 100 + 200 + 150 = 450
        $this->assertEquals(450, $table['John']['amount']['_val']);
    }
    
    /**
     * Test with all null values
     */
    public function testAllNullValues()
    {
        $data = [
            ['name' => 'John', 'amount' => null],
            ['name' => 'Jane', 'amount' => null]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayHasKey('Jane', $table);
    }
    
    /**
     * Test percentage calculation with zero sum
     */
    public function testPercentageWithZeroSum()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 0],
            ['name' => 'John', 'category' => 'B', 'amount' => 0]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_PERC_ROW)
            ->generate();
        
        // Should not throw division by zero error
        $table = $pivot->getTable();
        
        $this->assertIsArray($table);
    }
    
    /**
     * Test with many columns
     */
    public function testManyColumns()
    {
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            $data[] = [
                'name' => 'John',
                'category' => 'Cat' . $i,
                'amount' => 100
            ];
        }
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertArrayHasKey('John', $table);
        // Should have 50 categories
        $keys = array_keys($table['John']);
        $nonSystemKeys = array_filter($keys, function($key) {
            return substr($key, 0, 1) !== '_';
        });
        $this->assertGreaterThanOrEqual(50, count($nonSystemKeys));
    }
    
    /**
     * Test with many rows
     */
    public function testManyRows()
    {
        $data = [];
        for ($i = 1; $i <= 100; $i++) {
            $data[] = [
                'name' => 'Person' . $i,
                'amount' => 100
            ];
        }
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Filter out system fields
        $realKeys = array_filter(array_keys($table), function($key) {
            return substr($key, 0, 1) !== '_';
        });
        
        // Should have 100 rows
        $this->assertCount(100, $realKeys);
    }
    
    /**
     * Test mixed data types in same field
     */
    public function testMixedDataTypes()
    {
        $data = [
            ['name' => 'John', 'value' => 100],
            ['name' => 'Jane', 'value' => '200'],
            ['name' => 'Bob', 'value' => 150.5]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('value', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should handle mixed types
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayHasKey('Jane', $table);
        $this->assertArrayHasKey('Bob', $table);
    }
}
