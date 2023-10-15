<?php

namespace MarketforceInfo\AzureTranslator\Tests\Integration;

use GuzzleHttp\Psr7\HttpFactory;
use MarketforceInfo\AzureTranslator\MessageFormatter\BasicFormatter;
use MarketforceInfo\AzureTranslator\Tests\ResponseFactory;
use MarketforceInfo\AzureTranslator\Translator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator
 */
class TranslatorTest extends TestCase
{
    private ClientInterface|MockObject $client;
    private ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createMock(ClientInterface::class);
        $this->responseFactory = new ResponseFactory($this);
    }

    public function testTranslate()
    {
        $messages = [1001 => 'Message {name} 1', 1002 => 'Message {name} 2'];
        $expected = [
            1001 => [
                'es' => 'Message {name} 1Es',
                'it' => 'Message {name} 1It',
            ],
            1002 => [
                'es' => 'Message {name} 2Es',
                'it' => 'Message {name} 2It',
            ]
        ];

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(
                $this->responseFactory
                    ->setTranslateBehaviour(function (Translator\Language $language, string $message) {
                        $this->assertStringContainsString(BasicFormatter::TAG_VAR_OPEN, $message);
                        return $message . ucfirst($language->value);
                    })
                    ->createSendRequestCallback()
            );

        $this->translator()
            ->onTranslate(function (Translator\Translation $translation) use (&$expected) {
                $index = $translation->state['id'];
                $this->assertTrue(isset($expected[$index][$translation->language->value]));
                $this->assertEquals($expected[$index][$translation->language->value], $translation->message);
            })
            ->begin(function (Translator\Delegate $translator) use ($messages) {
                foreach ($messages as $index => $message) {
                    $translator->translate($message, ['id' => $index]);
                }
            });
    }

    private function translator(): Translator
    {
        $requestFactory = $streamFactory = new HttpFactory();
        $client = new Translator\Client(
            $this->client,
            new Translator\RequestFactory(
                $requestFactory,
                $streamFactory,
                Translator\Client::BASE_URL_ASIA,
                Translator\Language::english,
                [Translator\Language::spanish, Translator\Language::italian]
            )
        );

        return new Translator($client, new BasicFormatter());
    }
}
