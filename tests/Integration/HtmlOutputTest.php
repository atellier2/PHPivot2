<?php

namespace Atellier2\PHPivot\Tests\Integration;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;
use Atellier2\PHPivot\Config\PivotConstants;

/**
 * Test HTML output generation
 * 
 * These tests verify that HTML is properly generated and formatted.
 */
class HtmlOutputTest extends TestCase
{
    /**
     * Test basic HTML structure
     */
    public function testBasicHtmlStructure()
    {
        $data = [
            ['name' => 'John', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Check basic HTML structure
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('</table>', $html);
        $this->assertStringContainsString('<thead>', $html);
        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('<th>', $html);
        $this->assertStringContainsString('<td>', $html);
    }
    
    /**
     * Test HTML with row and column fields
     */
    public function testHtmlWithRowAndColumnFields()
    {
        $data = [
            ['name' => 'John', 'year' => '2020', 'amount' => 100],
            ['name' => 'Jane', 'year' => '2021', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('year')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should contain row and column headers
        $this->assertStringContainsString('John', $html);
        $this->assertStringContainsString('Jane', $html);
        $this->assertStringContainsString('2020', $html);
        $this->assertStringContainsString('2021', $html);
    }
    
    /**
     * Test HTML with custom titles
     */
    public function testHtmlWithCustomTitles()
    {
        $data = [
            ['name' => 'John', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name', 'Person Name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_VALUE, 'Total Amount')
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should contain custom titles
        $this->assertStringContainsString('Person Name', $html);
        // Note: Column title might not show in all cases
    }
    
    /**
     * Test HTML output is well-formed
     */
    public function testHtmlOutputIsWellFormed()
    {
        $data = [
            ['name' => 'John', 'city' => 'NYC', 'amount' => 100],
            ['name' => 'Jane', 'city' => 'LA', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('city')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Basic checks for well-formed HTML - table tags should match
        $this->assertEquals(
            substr_count($html, '<table>'),
            substr_count($html, '</table>'),
            'Table opening and closing tags should match'
        );
        
        // TR tags should match
        $this->assertEquals(
            substr_count($html, '<tr>'),
            substr_count($html, '</tr>'),
            'TR opening and closing tags should match'
        );
    }
    
    /**
     * Test 2D array HTML output
     */
    public function testTwoDArrayHtmlOutput()
    {
        $data = [
            'Row1' => ['Col1' => 10, 'Col2' => 20],
            'Row2' => ['Col1' => 30, 'Col2' => 40]
        ];
        
        $pivot = PHPivot::createFrom2DArray($data, 'Columns', 'Rows')
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Check that data is present
        $this->assertStringContainsString('Row1', $html);
        $this->assertStringContainsString('Row2', $html);
        $this->assertStringContainsString('10', $html);
        $this->assertStringContainsString('40', $html);
        $this->assertStringContainsString('Columns', $html);
        $this->assertStringContainsString('Rows', $html);
    }
    
    /**
     * Test 1D array HTML output
     */
    public function testOneDArrayHtmlOutput()
    {
        $data = ['Item1' => 10, 'Item2' => 20, 'Item3' => 30];
        
        $pivot = PHPivot::createFrom1DArray($data, 'Values', 'Items')
            ->generate();
        
        $html = $pivot->toHtml();
        
        $this->assertStringContainsString('Item1', $html);
        $this->assertStringContainsString('Item2', $html);
        $this->assertStringContainsString('Item3', $html);
        $this->assertStringContainsString('10', $html);
        $this->assertStringContainsString('30', $html);
    }
    
    /**
     * Test percentage display in HTML
     */
    public function testPercentageDisplayInHtml()
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
        
        $html = $pivot->toHtml();
        
        // Should contain percentage symbol
        $this->assertStringContainsString('%', $html);
    }
}
