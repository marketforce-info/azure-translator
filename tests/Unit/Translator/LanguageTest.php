<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\Translator;

use MarketforceInfo\AzureTranslator\Exceptions\InvalidArgumentException;
use MarketforceInfo\AzureTranslator\Translator\Language;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator\Language
 */
class LanguageTest extends TestCase
{
    /**
     * @dataProvider validLanguageProvider
     */
    public function testVerifyValidLanguageList(array $languages, Language $exclude)
    {
        Language::verify($languages, $exclude);
        $this->assertTrue(true);
    }

    public function validLanguageProvider()
    {
        return [
            'single' => [[Language::english], Language::french],
            'double' => [[Language::french, Language::italian], Language::english],
            'more' => [[Language::french, Language::italian, Language::english], Language::spanish],
        ];
    }

    /**
     * @dataProvider invalidLanguageProvider
     */
    public function testVerifyInvalidLanguageList(array $languages, Language $exclude)
    {
        $this->expectException(InvalidArgumentException::class);
        Language::verify($languages, $exclude);
    }

    public function invalidLanguageProvider()
    {
        return [
            'empty' => [[], Language::english],
            'duplicate' => [[Language::french, Language::italian, Language::italian], Language::english],
            'has-exclude' => [[Language::french, Language::italian, Language::english], Language::french],
            'not-enum' => [['en', 'fr', 'it'], Language::english],
        ];
    }

    public function testAsQueryParam()
    {
        $this->assertEquals(
            'to=en&to=fr&to=it',
            Language::asQueryParam([Language::english, Language::french, Language::italian])
        );
    }

    public function testToValues()
    {
        $this->assertEquals(
            ['en', 'fr', 'it'],
            Language::toValues([Language::english, Language::french, Language::italian])
        );
    }
}
