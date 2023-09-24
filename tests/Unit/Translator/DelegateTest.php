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
        $delegate = new Delegate(fn () => $this->assertTrue(true));
        $delegate->translate('some message', []);
    }
}
