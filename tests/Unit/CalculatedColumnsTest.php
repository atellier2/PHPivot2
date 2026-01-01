<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;
use Atellier2\PHPivot\Config\PivotConstants;

/**
 * Test calculated columns functionality
 * 
 * These tests verify that calculated columns can be added and work correctly.
 */
class CalculatedColumnsTest extends TestCase
{
    /**
     * Test adding a simple calculated column
     */
    public function testAddSimpleCalculatedColumn()
    {
        $data = [
            ['name' => 'John', 'price' => 100, 'quantity' => 2],
            ['name' => 'Jane', 'price' => 150, 'quantity' => 3]
        ];
        
        $pivot = PHPivot::create($data)
            ->addCalculatedColumns('total', function($recordset, $rowID) {
                return $recordset[$rowID]['price'] * $recordset[$rowID]['quantity'];
            })
            ->setPivotRowFields('name')
            ->setPivotValueFields('total', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        // John: 100 * 2 = 200
        $this->assertEquals(200, $table['John']['total']['_val']);
        // Jane: 150 * 3 = 450
        $this->assertEquals(450, $table['Jane']['total']['_val']);
    }
    
    /**
     * Test calculated column returning array (multiple columns)
     */
    public function testCalculatedColumnReturningArray()
    {
        $data = [
            ['name' => 'John', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->addCalculatedColumns('calc', function($recordset, $rowID) {
                return [
                    'doubled' => $recordset[$rowID]['amount'] * 2,
                    'tripled' => $recordset[$rowID]['amount'] * 3
                ];
            })
            ->setPivotRowFields('name')
            ->setPivotValueFields(['calc_doubled', 'calc_tripled'], PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should have both calculated columns
        $this->assertArrayHasKey('calc_doubled', $table['John']);
        $this->assertArrayHasKey('calc_tripled', $table['John']);
        $this->assertEquals(200, $table['John']['calc_doubled']['_val']);
        $this->assertEquals(300, $table['John']['calc_tripled']['_val']);
    }
    
    /**
     * Test calculated column with extra parameters
     */
    public function testCalculatedColumnWithExtraParams()
    {
        $data = [
            ['name' => 'John', 'amount' => 100]
        ];
        
        $multiplier = 5;
        
        $pivot = PHPivot::create($data)
            ->addCalculatedColumns('result', function($recordset, $rowID, $extra) {
                return $recordset[$rowID]['amount'] * $extra;
            }, $multiplier)
            ->setPivotRowFields('name')
            ->setPivotValueFields('result', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertEquals(500, $table['John']['result']['_val']);
    }
    
    /**
     * Test multiple calculated columns
     */
    public function testMultipleCalculatedColumns()
    {
        $data = [
            ['name' => 'John', 'value' => 10]
        ];
        
        $pivot = PHPivot::create($data)
            ->addCalculatedColumns(
                ['doubled', 'squared'],
                [
                    function($rs, $id) { return $rs[$id]['value'] * 2; },
                    function($rs, $id) { return $rs[$id]['value'] * $rs[$id]['value']; }
                ],
                [null, null]
            )
            ->setPivotRowFields('name')
            ->setPivotValueFields(['doubled', 'squared'], PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        $this->assertEquals(20, $table['John']['doubled']['_val']);
        $this->assertEquals(100, $table['John']['squared']['_val']);
    }
    
    /**
     * Test calculated column used in filtering
     */
    public function testCalculatedColumnUsedInFilter()
    {
        $data = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 35],
            ['name' => 'Bob', 'age' => 45]
        ];
        
        $pivot = PHPivot::create($data)
            ->addCalculatedColumns('age_group', function($rs, $id) {
                return $rs[$id]['age'] >= 30 ? 'senior' : 'junior';
            })
            ->addFilter('age_group', 'senior', PivotConstants::COMPARE_EQUAL)
            ->setPivotRowFields('name')
            ->setPivotValueFields('age', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $table = $pivot->getTable();
        
        // Should only have Jane and Bob (age >= 30)
        $this->assertArrayNotHasKey('John', $table);
        $this->assertArrayHasKey('Jane', $table);
        $this->assertArrayHasKey('Bob', $table);
    }
}
