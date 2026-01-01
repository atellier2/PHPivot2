<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;

/**
 * Test filtering functionality
 * 
 * These tests verify that data filtering works correctly with
 * various filter types and patterns.
 */
class FilteringTest extends TestCase
{
    private $testData;
    
    protected function setUp(): void
    {
        $this->testData = [
            ['name' => 'John', 'age' => 30, 'city' => 'NYC', 'amount' => 100],
            ['name' => 'Jane', 'age' => 25, 'city' => 'LA', 'amount' => 200],
            ['name' => 'Bob', 'age' => 35, 'city' => 'NYC', 'amount' => 150],
            ['name' => 'Alice', 'age' => 28, 'city' => 'Chicago', 'amount' => 175],
            ['name' => '', 'age' => 40, 'city' => 'Boston', 'amount' => 125]
        ];
    }
    
    /**
     * Test basic equal filter
     */
    public function testBasicEqualFilter()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('city', 'NYC', PHPivot::COMPARE_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should only have John and Bob (both from NYC)
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayHasKey('Bob', $table);
        $this->assertArrayNotHasKey('Jane', $table);
        $this->assertArrayNotHasKey('Alice', $table);
    }
    
    /**
     * Test not equal filter
     */
    public function testNotEqualFilter()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('city', 'NYC', PHPivot::COMPARE_NOT_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should not have John and Bob (from NYC)
        $this->assertArrayNotHasKey('John', $table);
        $this->assertArrayNotHasKey('Bob', $table);
        $this->assertArrayHasKey('Jane', $table);
        $this->assertArrayHasKey('Alice', $table);
    }
    
    /**
     * Test filter with empty string (filter out blanks)
     */
    public function testFilterOutBlankValues()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('name', '', PHPivot::COMPARE_NOT_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should not have the empty name entry
        $this->assertArrayNotHasKey('', $table);
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayHasKey('Jane', $table);
    }
    
    /**
     * Test wildcard pattern filter
     */
    public function testWildcardPatternFilter()
    {
        $data = [
            ['name' => 'John Smith', 'amount' => 100],
            ['name' => 'John Doe', 'amount' => 200],
            ['name' => 'Jane Doe', 'amount' => 150]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('name', 'John*', PHPivot::COMPARE_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should have both Johns
        $this->assertArrayHasKey('John Smith', $table);
        $this->assertArrayHasKey('John Doe', $table);
        $this->assertArrayNotHasKey('Jane Doe', $table);
    }
    
    /**
     * Test question mark wildcard filter
     */
    public function testQuestionMarkWildcardFilter()
    {
        $data = [
            ['code' => 'A1', 'amount' => 100],
            ['code' => 'A2', 'amount' => 200],
            ['code' => 'B1', 'amount' => 150]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('code')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('code', 'A?', PHPivot::COMPARE_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should have both A1 and A2
        $this->assertArrayHasKey('A1', $table);
        $this->assertArrayHasKey('A2', $table);
        $this->assertArrayNotHasKey('B1', $table);
    }
    
    /**
     * Test multiple filters (AND logic)
     */
    public function testMultipleFilters()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('city', 'NYC', PHPivot::COMPARE_EQUAL)
            ->addFilter('name', 'John', PHPivot::COMPARE_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should only have John (NYC AND name=John)
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayNotHasKey('Bob', $table);
        $this->assertArrayNotHasKey('Jane', $table);
    }
    
    /**
     * Test filter with array of values - MATCH_ALL
     * Note: MATCH_ALL requires ALL values in the array to match, which is a strict condition
     */
    public function testFilterWithArrayMatchAll()
    {
        $data = [
            ['name' => 'John', 'tags' => 'abc', 'amount' => 100],
            ['name' => 'Jane', 'tags' => 'abc', 'amount' => 200]  // Changed to test actual matching
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('tags', ['abc'], PHPivot::COMPARE_EQUAL, PHPivot::FILTER_MATCH_ALL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Both should match since both have 'abc'
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayHasKey('Jane', $table);
    }
    
    /**
     * Test filter with array of values - MATCH_ANY
     */
    public function testFilterWithArrayMatchAny()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('city', ['NYC', 'LA'], PHPivot::COMPARE_EQUAL, PHPivot::FILTER_MATCH_ANY)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should have John, Jane, and Bob (NYC or LA)
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayHasKey('Jane', $table);
        $this->assertArrayHasKey('Bob', $table);
        $this->assertArrayNotHasKey('Alice', $table);
    }
    
    /**
     * Test filter with array of values - MATCH_NONE
     */
    public function testFilterWithArrayMatchNone()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('city', ['NYC', 'LA'], PHPivot::COMPARE_EQUAL, PHPivot::FILTER_MATCH_NONE)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should not have John, Jane, or Bob
        $this->assertArrayNotHasKey('John', $table);
        $this->assertArrayNotHasKey('Jane', $table);
        $this->assertArrayNotHasKey('Bob', $table);
        $this->assertArrayHasKey('Alice', $table);
    }
    
    /**
     * Test numeric filter
     */
    public function testNumericFilter()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('age', 30, PHPivot::COMPARE_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should only have John (age 30)
        $this->assertArrayHasKey('John', $table);
        $this->assertArrayNotHasKey('Jane', $table);
        $this->assertArrayNotHasKey('Bob', $table);
    }
    
    /**
     * Test that filter on non-existent column throws exception
     */
    public function testFilterOnNonExistentColumnThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No such column');
        
        PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('nonexistent', 'value', PHPivot::COMPARE_EQUAL)
            ->generate();
    }
    
    /**
     * Test chaining multiple filters
     */
    public function testChainingMultipleFilters()
    {
        $pivot = PHPivot::create($this->testData)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PHPivot::PIVOT_VALUE_SUM)
            ->addFilter('name', '', PHPivot::COMPARE_NOT_EQUAL)
            ->addFilter('city', 'NYC', PHPivot::COMPARE_NOT_EQUAL)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should have Jane and Alice (not blank, not NYC)
        $this->assertArrayHasKey('Jane', $table);
        $this->assertArrayHasKey('Alice', $table);
        $this->assertArrayNotHasKey('John', $table);
        $this->assertArrayNotHasKey('Bob', $table);
        $this->assertArrayNotHasKey('', $table);
    }
}
