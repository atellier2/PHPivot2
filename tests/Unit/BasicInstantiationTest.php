<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;
use Atellier2\PHPivot\Config\PivotConstants;
use Atellier2\PHPivot\Exception\PHPivotException;

/**
 * Test basic instantiation and configuration of PHPivot
 * 
 * These tests verify that the library can be properly instantiated
 * and configured with basic settings.
 */
class BasicInstantiationTest extends TestCase
{
    /**
     * Test that PHPivot can be instantiated with create() static method
     */
    public function testCreateWithValidArray()
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];
        
        $pivot = PHPivot::create($data);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that PHPivot can be instantiated with constructor
     */
    public function testConstructorWithValidArray()
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];
        
        $pivot = new PHPivot($data);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that constructor throws exception for non-array input
     */
    public function testConstructorThrowsExceptionForNonArray()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_recordset'));
        
        new PHPivot('not an array');
    }
    
    /**
     * Test that empty array is accepted
     */
    public function testConstructorAcceptsEmptyArray()
    {
        $pivot = PHPivot::create([]);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test setting pivot row fields
     */
    public function testSetPivotRowFields()
    {
        $data = [['name' => 'John', 'age' => 30]];
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name');
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        $this->assertEquals(['name'], $pivot->getRows());
    }
    
    /**
     * Test setting pivot row fields with array
     */
    public function testSetPivotRowFieldsWithArray()
    {
        $data = [['name' => 'John', 'age' => 30, 'city' => 'NYC']];
        $pivot = PHPivot::create($data)
            ->setPivotRowFields(['name', 'city']);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        $this->assertEquals(['name', 'city'], $pivot->getRows());
    }
    
    /**
     * Test setting pivot column fields
     */
    public function testSetPivotColumnFields()
    {
        $data = [['name' => 'John', 'age' => 30]];
        $pivot = PHPivot::create($data)
            ->setPivotColumnFields('age');
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        $this->assertEquals(['age'], $pivot->getColumns());
    }
    
    /**
     * Test setting pivot column fields with array
     */
    public function testSetPivotColumnFieldsWithArray()
    {
        $data = [['name' => 'John', 'age' => 30, 'city' => 'NYC']];
        $pivot = PHPivot::create($data)
            ->setPivotColumnFields(['age', 'city']);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
        $this->assertEquals(['age', 'city'], $pivot->getColumns());
    }
    
    /**
     * Test setting pivot value fields
     */
    public function testSetPivotValueFields()
    {
        $data = [['name' => 'John', 'amount' => 100]];
        $pivot = PHPivot::create($data)
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test method chaining
     */
    public function testMethodChaining()
    {
        $data = [['name' => 'John', 'age' => 30, 'amount' => 100]];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('age')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test createFrom2DArray static method
     */
    public function testCreateFrom2DArray()
    {
        $data = [
            'Row1' => ['Col1' => 10, 'Col2' => 20],
            'Row2' => ['Col1' => 30, 'Col2' => 40]
        ];
        
        $pivot = PHPivot::createFrom2DArray($data, 'Columns', 'Rows');
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test createFrom1DArray static method
     */
    public function testCreateFrom1DArray()
    {
        $data = ['Item1' => 10, 'Item2' => 20, 'Item3' => 30];
        
        $pivot = PHPivot::createFrom1DArray($data, 'Values', 'Items');
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test setting decimal precision
     */
    public function testSetDecimalPrecision()
    {
        $data = [['name' => 'John', 'amount' => 100.5]];
        $pivot = PHPivot::create($data)
            ->setDecimalPrecision(2);
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
    
    /**
     * Test that decimal precision must be non-negative
     */
    public function testSetDecimalPrecisionThrowsExceptionForNegative()
    {
        $this->expectException(PHPivotException::class);
        $this->expectExceptionMessage(__('error.invalid_precision'));
        
        $data = [['name' => 'John', 'amount' => 100.5]];
        PHPivot::create($data)->setDecimalPrecision(-1);
    }
    
    /**
     * Test setIgnoreBlankValues
     */
    public function testSetIgnoreBlankValues()
    {
        $data = [['name' => 'John', 'amount' => 100]];
        $pivot = PHPivot::create($data)
            ->setIgnoreBlankValues();
        
        $this->assertInstanceOf(PHPivot::class, $pivot);
    }
}
