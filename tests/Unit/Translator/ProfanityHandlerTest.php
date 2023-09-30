<?php

namespace Translator;

use MarketforceInfo\AzureTranslator\Translator\ProfanityHandler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator\ProfanityHandler
 */
class ProfanityHandlerTest extends TestCase
{
    /**
     * @dataProvider untaggedActionsProvider
     */
    public function testUntaggedActions(string $action)
    {
        $expected = 'This is a transformed message.';
        $handler = new ProfanityHandler($action, null);
        $this->assertEquals($expected, $handler($expected));
    }

    public function untaggedActionsProvider()
    {
        return [
            'deleted' => [ProfanityHandler::ACTION_DELETED],
            'marked' => [ProfanityHandler::ACTION_MARKED],
        ];
    }

    /**
     * @dataProvider taggedActionProvider
     */
    public function testTaggedAction($message, $expected)
    {
        $handler = new ProfanityHandler(ProfanityHandler::ACTION_MARKED, fn ($profanity) => '^^^');
        $this->assertEquals($expected, $handler($message));
    }

    public function taggedActionProvider()
    {
        return [
            'with-tags' => [
                'This is a <profanity>marked</profanity> message.',
                'This is a ^^^ message.'
            ],
            'with-multiple-tags' => [
                'This is a <profanity>marked1</profanity> and <profanity>marked2</profanity> message.',
                'This is a ^^^ and ^^^ message.'
            ],
            'no-tags' => [
                'This is a unmarked message.',
                'This is a unmarked message.'
            ],
        ];
    }
}
