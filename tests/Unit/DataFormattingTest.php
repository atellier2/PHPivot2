<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;
use Atellier2\PHPivot\Config\PivotConstants;

/**
 * Test data formatting and display modes
 * 
 * These tests verify that data is formatted correctly according to
 * the display mode (percentage, value, etc.).
 */
class DataFormattingTest extends TestCase
{
    /**
     * Test DISPLAY_AS_VALUE (default)
     */
    public function testDisplayAsValue()
    {
        $data = [
            ['name' => 'John', 'amount' => 100],
            ['name' => 'Jane', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_VALUE)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Values should be plain numbers
        $this->assertEquals(100, $table['John']['amount']['_val']);
        $this->assertEquals(200, $table['Jane']['amount']['_val']);
    }
    
    /**
     * Test DISPLAY_AS_PERC_ROW
     */
    public function testDisplayAsPercRow()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 100],
            ['name' => 'John', 'category' => 'B', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_PERC_ROW)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should be percentages: 100 out of 300 = 33.33%, 200 out of 300 = 66.67%
        $this->assertIsNumeric($table['John']['A']['amount']['_val']);
        $this->assertIsNumeric($table['John']['B']['amount']['_val']);
        
        // Rough check (33.33 and 66.67 with rounding)
        $valA = $table['John']['A']['amount']['_val'];
        $valB = $table['John']['B']['amount']['_val'];
        
        $this->assertGreaterThan(30, $valA);
        $this->assertLessThan(35, $valA);
        $this->assertGreaterThan(65, $valB);
        $this->assertLessThan(70, $valB);
    }
    
    /**
     * Test DISPLAY_AS_VALUE_AND_PERC_ROW
     */
    public function testDisplayAsValueAndPercRow()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 100],
            ['name' => 'John', 'category' => 'B', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_VALUE_AND_PERC_ROW)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should contain both percentage and value
        $valA = $table['John']['A']['amount']['_val'];
        $valB = $table['John']['B']['amount']['_val'];
        
        // Should be strings containing both % and value
        $this->assertIsString($valA);
        $this->assertIsString($valB);
        $this->assertStringContainsString('%', $valA);
        $this->assertStringContainsString('100', $valA);
        $this->assertStringContainsString('%', $valB);
        $this->assertStringContainsString('200', $valB);
    }
    
    /**
     * Test DISPLAY_AS_PERC_COL
     */
    public function testDisplayAsPercCol()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 100],
            ['name' => 'Jane', 'category' => 'A', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_PERC_COL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should be percentages: 100 out of 300 = 33.33%, 200 out of 300 = 66.67%
        $valJohn = $table['John']['A']['amount']['_val'];
        $valJane = $table['Jane']['A']['amount']['_val'];
        
        $this->assertIsNumeric($valJohn);
        $this->assertIsNumeric($valJane);
        
        // Rough check
        $this->assertGreaterThan(30, $valJohn);
        $this->assertLessThan(35, $valJohn);
        $this->assertGreaterThan(65, $valJane);
        $this->assertLessThan(70, $valJane);
    }
    
    /**
     * Test DISPLAY_AS_VALUE_AND_PERC_COL
     */
    public function testDisplayAsValueAndPercCol()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 100],
            ['name' => 'Jane', 'category' => 'A', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_VALUE_AND_PERC_COL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should contain both percentage and value
        $valJohn = $table['John']['A']['amount']['_val'];
        $valJane = $table['Jane']['A']['amount']['_val'];
        
        $this->assertIsString($valJohn);
        $this->assertIsString($valJane);
        $this->assertStringContainsString('%', $valJohn);
        $this->assertStringContainsString('100', $valJohn);
        $this->assertStringContainsString('%', $valJane);
        $this->assertStringContainsString('200', $valJane);
    }
    
    /**
     * Test decimal precision setting
     */
    public function testDecimalPrecision()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 100],
            ['name' => 'John', 'category' => 'B', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_PERC_ROW)
            ->setDecimalPrecision(2)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Values should have proper decimal precision
        $valA = $table['John']['A']['amount']['_val'];
        $valB = $table['John']['B']['amount']['_val'];
        
        $this->assertIsNumeric($valA);
        $this->assertIsNumeric($valB);
    }
    
    /**
     * Test setIgnoreBlankValues
     */
    public function testIgnoreBlankValues()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 100],
            ['name' => 'Jane', 'category' => 'B', 'amount' => ''],
            ['name' => 'Bob', 'category' => 'C', 'amount' => 0]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->setIgnoreBlankValues()
            ->generate();
        
        $table = $pivot->getTable();
        
        // Jane with empty amount should be filtered out
        $this->assertArrayNotHasKey('Jane', $table);
        $this->assertArrayHasKey('John', $table);
    }
    
    /**
     * Test COUNT vs SUM
     */
    public function testCountVsSum()
    {
        $data = [
            ['name' => 'John', 'amount' => 100],
            ['name' => 'John', 'amount' => 200],
            ['name' => 'Jane', 'amount' => 150]
        ];
        
        // Test COUNT
        $pivotCount = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_COUNT)
            ->generate();
        
        $tableCount = $pivotCount->getTable();
        
        // John appears 2 times
        $this->assertEquals(2, $tableCount['John']['amount']['_val']);
        // Jane appears 1 time
        $this->assertEquals(1, $tableCount['Jane']['amount']['_val']);
        
        // Test SUM
        $pivotSum = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $tableSum = $pivotSum->getTable();
        
        // John sum: 100 + 200 = 300
        $this->assertEquals(300, $tableSum['John']['amount']['_val']);
        // Jane sum: 150
        $this->assertEquals(150, $tableSum['Jane']['amount']['_val']);
    }
}
