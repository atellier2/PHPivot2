<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\PHPivot;
use PHPUnit\Framework\TestCase;
use Atellier2\PHPivot\Config\PivotConstants;

/**
 * Test security features, particularly XSS prevention
 * 
 * These tests verify that the library properly escapes user-provided
 * data to prevent XSS attacks.
 */
class SecurityTest extends TestCase
{
    /**
     * Test that script tags in data are escaped in HTML output
     */
    public function testXSSPreventionInDataValues()
    {
        $data = [
            ['name' => '<script>alert("XSS")</script>', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should not contain raw script tag
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $html);
        // Should contain escaped version
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
    
    /**
     * Test that HTML entities in column names are escaped
     */
    public function testXSSPreventionInColumnNames()
    {
        $data = [
            ['name' => 'John', 'category' => '<b>Bold</b>', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should not contain raw HTML tags
        $this->assertStringNotContainsString('<b>Bold</b>', $html);
        // Should contain escaped version
        $this->assertStringContainsString('&lt;b&gt;', $html);
    }
    
    /**
     * Test that quotes in data are properly escaped
     */
    public function testQuotesAreEscaped()
    {
        $data = [
            ['name' => 'John "The Rock" Doe', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should contain escaped quotes
        $this->assertStringContainsString('&quot;', $html);
    }
    
    /**
     * Test that ampersands are properly escaped
     */
    public function testAmpersandsAreEscaped()
    {
        $data = [
            ['name' => 'Smith & Sons', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should contain escaped ampersand
        $this->assertStringContainsString('&amp;', $html);
    }
    
    /**
     * Test that single quotes are properly escaped
     */
    public function testSingleQuotesAreEscaped()
    {
        $data = [
            ['name' => "O'Brien", 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should contain escaped single quote (both &#039; and &apos; are valid HTML5 entities)
        $hasEntity = strpos($html, '&#039;') !== false || strpos($html, '&apos;') !== false;
        $this->assertTrue($hasEntity, 'Single quote should be escaped');
    }
    
    /**
     * Test that multiple XSS vectors are all escaped
     */
    public function testMultipleXSSVectorsAreEscaped()
    {
        $data = [
            [
                'name' => '<img src=x onerror=alert(1)>',
                'category' => '<iframe src="javascript:alert(2)">',
                'amount' => 100
            ]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should not contain dangerous tags with < and >
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<iframe src=', $html);
        // Should contain escaped versions
        $this->assertStringContainsString('&lt;', $html);
        $this->assertStringContainsString('&gt;', $html);
    }
    
    /**
     * Test that null values don't cause issues
     */
    public function testNullValuesAreHandledSafely()
    {
        $data = [
            ['name' => null, 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should not throw error and should produce valid HTML
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<table>', $html);
    }
    
    /**
     * Test that numeric values are safe
     */
    public function testNumericValuesAreSafe()
    {
        $data = [
            ['name' => 'John', 'amount' => 12345]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should contain the numeric value
        $this->assertStringContainsString('12345', $html);
    }
    
    /**
     * Test that special Unicode characters are handled safely
     */
    public function testUnicodeCharactersAreHandledSafely()
    {
        $data = [
            ['name' => 'José García', 'amount' => 100],
            ['name' => '北京市', 'amount' => 200]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should handle Unicode properly
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<table>', $html);
    }
    
    /**
     * Test that row titles are escaped
     */
    public function testRowTitlesAreEscaped()
    {
        $data = [
            ['name' => 'John', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name', '<script>alert(1)</script>')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM)
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should not contain raw script tag
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        // Should contain escaped version
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
    
    /**
     * Test that column titles are escaped
     */
    public function testColumnTitlesAreEscaped()
    {
        $data = [
            ['name' => 'John', 'category' => 'A', 'amount' => 100]
        ];
        
        $pivot = PHPivot::create($data)
            ->setPivotRowFields('name')
            ->setPivotColumnFields('category')
            ->setPivotValueFields('amount', PivotConstants::PIVOT_VALUE_SUM, PivotConstants::DISPLAY_AS_VALUE, '<img src=x>')
            ->generate();
        
        $html = $pivot->toHtml();
        
        // Should not contain dangerous tag
        $this->assertStringNotContainsString('<img src=x>', $html);
    }
}
