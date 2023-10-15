<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\Translator;

use MarketforceInfo\AzureTranslator\Translator\Delegate;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator\Delegate
 */
class DelegateTest extends TestCase
{
    public function testPassesThrough()
    {
        $expectedMessage = 'some message';
        $delegate = new Delegate(function ($message) use ($expectedMessage) {
            $this->assertEquals($expectedMessage, $message);
        });
        $delegate->translate($expectedMessage);
    }
}
