<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\Translator;

use MarketforceInfo\AzureTranslator\Tests\ResponseFactory;
use MarketforceInfo\AzureTranslator\Translator\Client;
use MarketforceInfo\AzureTranslator\Translator\Language;
use MarketforceInfo\AzureTranslator\Translator\Messages;
use MarketforceInfo\AzureTranslator\Translator\RequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator\Client
 */
class ClientTest extends TestCase
{
    private ClientInterface|MockObject $httpClient;
    private RequestFactory|MockObject $requestFactory;
    private ResponseFactory $responseFactory;
    private Messages|MockObject $messages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->responseFactory = new ResponseFactory($this);
        $this->messages = $this->createMock(Messages::class);
    }

    public function testSuccessfulTranslation()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->responseFactory->create(
                $languages = [Language::french, Language::italian],
                ['Foo', 'Bar']
            ));

        $languageValues = Language::toValues($languages);
        $client = new Client($this->httpClient, $this->requestFactory);
        foreach ($client->translate($this->messages) as $position => $translation) {
            $this->assertIsInt($position);
            $this->assertArrayHasKey('text', $translation);
            $this->assertArrayHasKey('to', $translation);
            $this->assertIsString($translation['text']);
            $this->assertContainsEquals($translation['to'], $languageValues);
            $this->assertArrayHasKey('clientTraceId', $translation);
            break;
        }
    }

    public function testCustomTraceId()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->responseFactory->create([Language::french], ['Foo']));

        $expectedTraceId = 'custom';
        $traceIdCallback = static fn () => $expectedTraceId;
        $client = new Client($this->httpClient, $this->requestFactory, $traceIdCallback);
        foreach ($client->translate($this->messages) as $translation) {
            $this->assertArrayHasKey('clientTraceId', $translation);
            $this->assertSame($expectedTraceId, $translation['clientTraceId']);
        }
    }

    /*
     * Failures
     * @see https://learn.microsoft.com/en-us/azure/ai-services/translator/reference/v3-0-translate#response-status-codes
     * 400 something wrong with the component - permanent failure
     * 401 permanent failure?? - using token could re-authenticate
     * 403 permanent failure
     * 408 unsupported - permanent failure
     * 429, 500, 503 retry - with backoff, possible different config by code
     */

    public function testInvalidRequest()
    {
        $message = 'The request is not authorized because credentials are missing or invalid.';
        $this->expectException(RequestExceptionInterface::class);
        $this->expectExceptionMessage($message);

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->responseFactory->createFailure(401, $message));

        $client = new Client($this->httpClient, $this->requestFactory);
        foreach ($client->translate($this->messages) as $translation) {}
    }
}
