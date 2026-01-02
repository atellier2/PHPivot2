<?php

namespace Atellier2\PHPivot\Tests\Unit;

use Atellier2\PHPivot\Service\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Test translation functions
 * 
 * These tests verify that the Translator service correctly handles:
 * - Translation initialization and configuration
 * - Message translation with and without parameters
 * - Locale management
 * - Fallback locale handling
 * - Translation existence checks
 */
class TranslatorTest extends TestCase
{
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Reset translator state between tests
        Translator::configure('en');
    }

    /**
     * Test that Translator can be initialized
     */
    public function testTranslatorInitialization()
    {
        Translator::configure('en');
        $this->assertEquals('en', Translator::getLocale());
    }

    /**
     * Test configuration with custom default locale
     */
    public function testConfigureWithCustomLocale()
    {
        Translator::configure('fr');
        $this->assertEquals('fr', Translator::getLocale());
    }

    /**
     * Test configuration with fallback locales
     */
    public function testConfigureWithFallbackLocales()
    {
        Translator::configure('fr', null, ['fr', 'en']);
        $this->assertEquals('fr', Translator::getLocale());
    }

    /**
     * Test setting locale
     */
    public function testSetLocale()
    {
        Translator::configure('en');
        Translator::setLocale('fr');
        $this->assertEquals('fr', Translator::getLocale());
    }

    /**
     * Test getting locale
     */
    public function testGetLocale()
    {
        Translator::configure('en');
        $locale = Translator::getLocale();
        $this->assertEquals('en', $locale);
    }

    /**
     * Test translation of a simple message
     */
    public function testTranslateSimpleMessage()
    {
        Translator::configure('en');
        $result = Translator::trans('error.invalid_recordset');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test translation with parameters
     */
    public function testTranslateWithParameters()
    {
        Translator::configure('en');
        $result = Translator::trans('error.invalid_color_format', ['%color%' => 'red']);
        $this->assertIsString($result);
        // Should contain the parameter replacement
        $this->assertStringContainsString('red', $result);
    }

    /**
     * Test translation in different locale
     */
    public function testTranslateInFrench()
    {
        Translator::configure('fr');
        $result = Translator::trans('error.invalid_recordset');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertNotEquals('Recordset must be an array', $result);
        $this->assertEquals("Le jeu d'enregistrements doit être un tableau", $result);
    }

    /**
     * Test translation with locale override
     */
    public function testTranslateWithLocaleOverride()
    {
        Translator::configure('en');
        $resultEn = Translator::trans('error.invalid_recordset', [], 'messages', 'en');
        $resultFr = Translator::trans('error.invalid_recordset', [], 'messages', 'fr');
        
        $this->assertIsString($resultEn);
        $this->assertIsString($resultFr);
    }

    /**
     * Test transChoice method
     */
    public function testTransChoice()
    {
        Translator::configure('en');
        $result = Translator::transChoice('error.invalid_recordset', 1);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test has() method for existing translation
     */
    public function testHasExistingTranslation()
    {
        Translator::configure('en');
        $exists = Translator::has('error.invalid_recordset');
        $this->assertTrue($exists);
    }

    /**
     * Test has() method for non-existing translation
     */
    public function testHasNonExistingTranslation()
    {
        Translator::configure('en');
        $exists = Translator::has('this.key.does.not.exist');
        $this->assertFalse($exists);
    }

    /**
     * Test has() method with locale override
     */
    public function testHasWithLocaleOverride()
    {
        Translator::configure('en');
        $exists = Translator::has('error.invalid_recordset', 'en');
        $this->assertTrue($exists);
    }

    /**
     * Test setting fallback locales
     */
    public function testSetFallbackLocales()
    {
        Translator::configure('en');
        Translator::setFallbackLocales(['en', 'fr']);
        // Should not throw exception
        $this->assertEquals('en', Translator::getLocale());
    }

    /**
     * Test loading all locales
     */
    public function testLoadAllLocales()
    {
        Translator::configure('en');
        // Should not throw exception
        Translator::loadAllLocales();
        $this->assertEquals('en', Translator::getLocale());
    }

    /**
     * Test getting the Symfony translator instance
     */
    public function testGetTranslator()
    {
        Translator::configure('en');
        $translator = Translator::getTranslator();
        $this->assertNotNull($translator);
    }

    /**
     * Test multiple translations in sequence
     */
    public function testMultipleTranslations()
    {
        Translator::configure('en');
        
        $trans1 = Translator::trans('error.invalid_recordset');
        $trans2 = Translator::trans('error.invalid_filter_column');
        $trans3 = Translator::trans('error.invalid_compare_operator');
        
        $this->assertIsString($trans1);
        $this->assertIsString($trans2);
        $this->assertIsString($trans3);
        // All should be different messages
        $this->assertNotEquals($trans1, $trans2);
        $this->assertNotEquals($trans2, $trans3);
    }

    /**
     * Test switching between locales
     */
    public function testSwitchBetweenLocales()
    {
        Translator::configure('en');
        $localeEn = Translator::getLocale();
        $this->assertEquals('en', $localeEn);
        
        Translator::setLocale('fr');
        $localeFr = Translator::getLocale();
        $this->assertEquals('fr', $localeFr);
        
        Translator::setLocale('en');
        $localeEnAgain = Translator::getLocale();
        $this->assertEquals('en', $localeEnAgain);
    }

    /**
     * Test translation caching (same translation called twice)
     */
    public function testTranslationCaching()
    {
        Translator::configure('en');
        
        $result1 = Translator::trans('error.invalid_recordset');
        $result2 = Translator::trans('error.invalid_recordset');
        
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test translation with empty parameters array
     */
    public function testTranslateWithEmptyParameters()
    {
        Translator::configure('en');
        $result = Translator::trans('error.invalid_recordset', []);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test translation with multiple parameters
     */
    public function testTranslateWithMultipleParameters()
    {
        Translator::configure('en');
        $result = Translator::trans('error.invalid_color_format', [
            '%color%' => 'blue',
            '%extra%' => 'value'
        ]);
        $this->assertIsString($result);
        $this->assertStringContainsString('blue', $result);
    }
}
