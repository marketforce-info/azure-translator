<?php

namespace Config;

use MarketforceInfo\AzureTranslator\Config\Language;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\AzureTranslator\Config\Language
 */
class LanguageTest extends TestCase
{
    public function testHasWithValidLanguage()
    {
        $this->assertTrue(Language::has(Language::english));
    }

    public function testHasWithValidLanguages()
    {
        $this->assertTrue(Language::has([Language::german, Language::french, Language::frenchCanada]));
    }

    public function testHasWithInvalidLanguages()
    {
        $this->assertFalse(Language::has([]));
    }
}
