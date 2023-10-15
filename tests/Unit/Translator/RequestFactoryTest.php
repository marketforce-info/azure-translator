<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\Translator;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use MarketforceInfo\AzureTranslator\Translator\Language;
use MarketforceInfo\AzureTranslator\Translator\Messages;
use MarketforceInfo\AzureTranslator\Translator\ProfanityHandler;
use MarketforceInfo\AzureTranslator\Translator\RequestFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator\RequestFactory
 */
class RequestFactoryTest extends TestCase
{
    private HttpFactory $requestFactory;
    private StreamFactoryInterface|MockObject $streamFactory;
    private Messages|MockObject $messages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestFactory = new HttpFactory();
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->messages = $this->createMock(Messages::class);

//        $this->requestFactory->expects($this->any())->method('createRequest')
//            ->willReturn(new Request('GET', ''));
        //$this->messages->expects($this->any())->method('jsonSerialize')->willReturn(['foo' => 'bar']);
    }

    public function testCreate()
    {
        $request = $this->instance()
            ->create($this->messages, 'trace-id');

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https', $request->getUri()->getScheme());
        $this->assertEquals('foo.com', $request->getUri()->getHost());
        $this->assertEquals('/translate', $request->getUri()->getPath());
        $this->assertStringContainsString(Language::russian->value, $request->getUri()->getQuery());
        $this->assertStringContainsString(Language::english->value, $request->getUri()->getQuery());
        $this->assertStringContainsString('textType=html', $request->getUri()->getQuery());
        $this->assertEquals('trace-id', $request->getHeader('X-ClientTraceId')[0]);
    }

    /**
     * @dataProvider withAuthenticationProvider
     */
    public function testWithAuthentication($header, $value)
    {
        $request = $this->instance(config: ['authentication' => [$header, $value]])
            ->create($this->messages, '');
        $this->assertTrue($request->hasHeader($header));
        $this->assertEquals($value, $request->getHeader($header)[0]);
    }

    public function withAuthenticationProvider(): array
    {
        return [
            'subscription' => ['X-SubscriptionKey', 'FooBar'],
            'bearer' => ['Authentication', 'Bearer FooBarBaz'],
        ];
    }

    public function testWithSubscriptionRegion()
    {
        $request = $this->instance(config: ['subscription_region' => 'NorthAmerica'])
            ->create($this->messages, '');
        $this->assertTrue($request->hasHeader('Ocp-Apim-Subscription-Region'));
        $this->assertEquals('NorthAmerica', $request->getHeader('Ocp-Apim-Subscription-Region')[0]);
    }

    public function testWithResourceId()
    {
        $request = $this->instance(config: ['resource_id' => 'foo-bar-baz'])
            ->create($this->messages, '');
        $this->assertTrue($request->hasHeader('Ocp-Apim-ResourceId'));
        $this->assertEquals('foo-bar-baz', $request->getHeader('Ocp-Apim-ResourceId')[0]);
    }

    public function testWithProfanityDisabled()
    {
        $request = $this->instance()
            ->create($this->messages, '');
        $this->assertStringContainsString('profanityAction=NoAction', $request->getUri()->getQuery());
    }

    public function testWithProfanityDeleted()
    {
        $profanityConfig = ['action' => ProfanityHandler::ACTION_DELETED, 'marker' => null];
        $request = $this->instance(config: ['profanity_handling' => $profanityConfig])
            ->create($this->messages, '');
        $this->assertStringContainsString('profanityAction=Deleted', $request->getUri()->getQuery());
    }

    /**
     * @dataProvider withProfanityMarkedProvider
     */
    public function testWithProfanityMarked($action, $marker)
    {
        $expectedMarkerValue = $marker === null ? ProfanityHandler::MARKER_ASTERISK : ProfanityHandler::MARKER_TAG;
        $request = $this->instance(config: ['profanity_handling' => ['action' => $action, 'marker' => $marker]])
            ->create($this->messages, '');
        $this->assertStringContainsString(
            'profanityAction=' . ProfanityHandler::ACTION_MARKED,
            $request->getUri()->getQuery()
        );
        $this->assertStringContainsString(
            'profanityMarker=' . $expectedMarkerValue,
            $request->getUri()->getQuery()
        );
    }

    public function withProfanityMarkedProvider(): array
    {
        return [
            'marked-asterisks' => [ProfanityHandler::ACTION_MARKED, null],
            'marked-custom' => [ProfanityHandler::ACTION_MARKED, fn () => null],
        ];
    }

    private function instance(
        string $baseUrl = 'https://foo.com',
        Language $from = Language::russian,
        array $to = [Language::english, Language::armenian],
        array $config = []
    ): RequestFactory {
        return new RequestFactory(
            $this->requestFactory,
            $this->streamFactory,
            $to,
            $from,
            $baseUrl,
            $config
        );
    }
}
