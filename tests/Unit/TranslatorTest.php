<?php

use Http\Mock\Client as HttpClient;
use MarketforceInfo\AzureTranslator\Translator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator
 */
class TranslatorTest extends TestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new HttpClient();
    }

    public function testDoesTranslation()
    {
        $translations = [
            ['text' => 'Bonjour', 'to' => 'fr'],
        ];
        $this->clientRespondsWith($translations);
        $translator = new Translator($this->client, ['fr'], function ($message, $language, $context) use ($translations) {
            $this->assertEquals($translations[0]['text'], $message);
            $this->assertEquals('fr', $language);
            var_dump($message, $language, $context); die;
        });
        $translator->session(fn () => $translator->translate('Hello'));
    }

    private function clientRespondsWith(array $translations): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $this->client->addResponse($response);
        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(GuzzleHttp\Psr7\Utils::streamFor(json_encode([
                'translations' => $translations,
            ], JSON_THROW_ON_ERROR)));
//
//
//        $response = $this->createMock(ResponseInterface::class);
//        $response
//            ->expects($this->once())
//            ->method('getBody')
//            ->willReturn(json_encode([
//                'translations' => $translations,
//            ], JSON_THROW_ON_ERROR));
//
//        $this->client
//            ->expects($this->once())
//            ->method('sendRequest')
//            ->willReturn($response);
    }
}
