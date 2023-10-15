<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit;

use MarketforceInfo\AzureTranslator\Exceptions\InvalidArgumentException;
use MarketforceInfo\AzureTranslator\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator
 */
class TranslatorTest extends TestCase
{
    private Translator\Client|MockObject $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Translator\Client::class);
    }

    public function testTranslates()
    {
        $expected = [
            ['text' => 'Bonjour', 'to' => 'fr', 'clientTraceId' => '1234567890', 'state' => []],
        ];

        $this->client
            ->expects($this->once())
            ->method('translate')
            ->willReturnCallback(fn () => yield from $expected);

        (new Translator($this->client))
            ->onTranslate(function (Translator\Translation $translation) use ($expected) {
                $this->assertEquals($expected[0]['text'], $translation->message);
                $this->assertSame(Translator\Language::french, $translation->language);
            })
            ->begin(fn (Translator\Delegate $translator) => $translator->translate('Hello'));
    }

    public function testOnHttpClientError()
    {
        $this->expectException(ClientExceptionInterface::class);

        $this->client
            ->expects($this->once())
            ->method('translate')
            ->willThrowException($this->createMock(ClientExceptionInterface::class));

        (new Translator($this->client))
            ->onTranslate(function (Translator\Translation $translation) {})
            ->begin(fn (Translator\Delegate $translator) => $translator->translate('Hello'));
    }

    public function testInvalidCharacterLimit()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->client->expects($this->never())->method('translate');

        $expectedLimit = 10;
        $config = ['characterLimit' => $expectedLimit];
        (new Translator($this->client, config: $config))
            ->onTranslate(function (Translator\Translation $translation) {})
            ->begin(fn (Translator\Delegate $translator) =>
                $translator->translate(str_pad('', $expectedLimit + 1, 'a')));
    }
}
