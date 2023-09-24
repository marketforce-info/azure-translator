<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\MessageFormatter;

use MarketforceInfo\AzureTranslator\MessageFormatter\MessageFormatter;
use MarketforceInfo\AzureTranslator\MessageFormatter\PassthruFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\AzureTranslator\MessageFormatter\PassthruFormatter
 */
class PassthruFormatterTest extends TestCase
{
    public function testImplementsInterface()
    {
        $this->assertInstanceOf(MessageFormatter::class, new PassthruFormatter());
    }

    public function testTo()
    {
        $formatter = new PassthruFormatter();
        $this->assertEquals('Hello world', $formatter->toAzure('Hello world'));
    }

    public function testFrom()
    {
        $formatter = new PassthruFormatter();
        $this->assertEquals('Hello world', $formatter->fromAzure('Hello world'));
    }
}
