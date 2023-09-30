<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\Translator;

use MarketforceInfo\AzureTranslator\Tests\ResponseFactory;
use MarketforceInfo\AzureTranslator\Translator\Client;
use MarketforceInfo\AzureTranslator\Translator\Language;
use MarketforceInfo\AzureTranslator\Translator\Messages;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator\Client
 */
class ClientTest extends TestCase
{
    private ClientInterface $httpClient;
    private RequestInterface $baseRequest;
    private StreamFactoryInterface $streamFactory;
    private ResponseFactory $responseFactory;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->responseFactory = new ResponseFactory($this);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->baseRequest = $this->createMock(RequestInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);

        $this->baseRequest->method('withHeader')->willReturnSelf();
        $this->baseRequest->method('withBody')->willReturnSelf();
    }

    public function testSuccessfulTranslation()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->responseFactory->create(
                $languages = [Language::french, Language::italian],
                ['Foo', 'Bar']
            ));

        $client = new Client($this->httpClient, $this->baseRequest, $this->streamFactory);
        foreach ($client->translate($this->messages([['Foo'], ['Bar']])) as $position => $translation) {
            $this->assertIsInt($position);
            $this->assertArrayHasKey('text', $translation);
            $this->assertArrayHasKey('to', $translation);
            $this->assertIsString($translation['text']);
            $this->assertContainsEquals($translation['to'], Language::toValues($languages));
            $this->assertArrayHasKey('clientTraceId', $translation);
        }
    }

    public function testCustomTraceId()
    {
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn($this->responseFactory->create([Language::french], ['Foo']));

        $expectedTraceId = 'custom';
        $traceIdCallback = static fn () => $expectedTraceId;
        $client = new Client($this->httpClient, $this->baseRequest, $this->streamFactory, $traceIdCallback);
        foreach ($client->translate($this->messages([['Foo']])) as $translation) {
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
        $this->expectException(RequestExceptionInterface::class);
        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($this->createMock(RequestExceptionInterface::class));

        $client = new Client($this->httpClient, $this->baseRequest, $this->streamFactory);
        foreach ($client->translate($this->messages([['Foo']])) as $translation) {
        }
    }

    private function messages(array $data): Messages
    {
        $collection = new Messages();
        foreach ($data as $item) {
            $collection->add(...$item);
        }
        return $collection;
    }
}
