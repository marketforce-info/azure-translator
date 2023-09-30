<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\MessageFormatter;

use MarketforceInfo\AzureTranslator\MessageFormatter\BasicFormatter;
use MarketforceInfo\AzureTranslator\MessageFormatter\MessageFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\AzureTranslator\MessageFormatter\BasicFormatter
 */
class BasicFormatterTest extends TestCase
{
    public function testImplementsInterface()
    {
        $this->assertInstanceOf(MessageFormatter::class, new BasicFormatter());
    }

    public function testToAzureNoSyntax()
    {
        $formatter = new BasicFormatter();
        $this->assertEquals('Hello', $formatter->toAzure('Hello'));
    }

    /**
     * @dataProvider toAzureProvider
     */
    public function testToAzure($startToken, $endToken, $message)
    {
        $formatter = new BasicFormatter($startToken, $endToken);
        $this->assertEquals(
            'Hello <t:var class="notranslate">name</t:var>',
            $formatter->toAzure($message)
        );
    }

    public function toAzureProvider()
    {
        return [
            'curly' => ['{', '}', 'Hello {name}'],
            'square' => ['[', ']', 'Hello [name]'],
            'tag' => ['<mytag>', '</mytag>', 'Hello <mytag>name</mytag>'],
        ];
    }

    public function testToAzureWithMultipleVariables()
    {
        $formatter = new BasicFormatter();
        $this->assertEquals(
            'Hello <t:var class="notranslate">name</t:var> welcome to <t:var class="notranslate">place</t:var> message',
            $formatter->toAzure('Hello {name} welcome to {place} message')
        );
    }

    public function testToAzureWithTokenMismatch()
    {
        $formatter = new BasicFormatter('[', ']');
        $this->assertEquals('Hello {name}', $formatter->toAzure('Hello {name}'));
    }

    public function testToAzureWithUnencodedMessage()
    {
        $formatter = new BasicFormatter();
        $this->assertEquals(
            'Hello <t:var class="notranslate">name &apos;</t:var>',
            $formatter->toAzure('Hello {name \'}')
        );
    }

    public function testFromAzureBasic()
    {
        $formatter = new BasicFormatter();
        $actual = $formatter->fromAzure('Salut <t:var class="notranslate">name</t:var>');
        $this->assertEquals('Salut {name}', $actual);
    }

    public function testFromAzureWithDifferentToken()
    {
        $formatter = new BasicFormatter('[', ']');
        $actual = $formatter->fromAzure('Salut <t:var class="notranslate">name</t:var>');
        $this->assertEquals('Salut [name]', $actual);
    }

    public function testFromAzureWithEncodedMessage()
    {
        $formatter = new BasicFormatter();
        $actual = $formatter->fromAzure('Salut <t:var class="notranslate">name &apos;</t:var>');
        $this->assertEquals('Salut {name \'}', $actual);
    }
}
