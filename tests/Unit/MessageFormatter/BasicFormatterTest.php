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

    public function testToAzure()
    {
        $formatter = new BasicFormatter();
        $this->assertEquals(
            'Hello <t:var class="notranslate">name</t:var>',
            $formatter->toAzure('Hello {name}')
        );
    }

    public function testToAzureWithMultipleVariables()
    {
        $formatter = new BasicFormatter();
        $this->assertEquals(
            'Hello <t:var class="notranslate">name</t:var> welcome to <t:var class="notranslate">place</t:var> message',
            $formatter->toAzure('Hello {name} welcome to {place} message')
        );
    }

    public function testToAzureWithDifferentSyntax()
    {
        $formatter = new BasicFormatter('[', ']');
        $actual = $formatter->toAzure('Hello [name]');
        $this->assertEquals('Hello <t:var class="notranslate">name</t:var>', $actual);
    }

    public function testToAzureWithSyntaxMismatch()
    {
        $formatter = new BasicFormatter('[', ']');
        $this->assertEquals('Hello {name}', $formatter->toAzure('Hello {name}'));
    }

    public function testFromAzureBasic()
    {
        $formatter = new BasicFormatter();
        $actual = $formatter->fromAzure('Salut <t:var class="notranslate">name</t:var>');
        $this->assertEquals('Salut {name}', $actual);
    }

    public function testFromAzureWithDifferentSyntax()
    {
        $formatter = new BasicFormatter('[', ']');
        $actual = $formatter->fromAzure('Salut <t:var class="notranslate">name</t:var>');
        $this->assertEquals('Salut [name]', $actual);
    }
}
