<?php

namespace MarketforceInfo\AzureTranslator\Tests;

use MarketforceInfo\AzureTranslator\Translator\Language;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseFactory
{
    private \Closure $translateBehaviour;

    public function __construct(private TestCase $test)
    {
        $this->translateBehaviour = static fn (Language $language, string $message) =>
            $message . ucfirst($language->value);
    }

    public function setTranslateBehaviour(\Closure $behaviour): self
    {
        $this->translateBehaviour = $behaviour;
        return $this;
    }

    public function create(array $languages, array $messages): ResponseInterface|MockObject
    {
        $bodyContent = $this->content($languages, $messages);
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn($bodyContent);
        $body->method('__toString')->willReturn($bodyContent);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($body);

        return $response;
    }

    public function createFailure(int $statusCode, $message): ResponseInterface|MockObject
    {
        $bodyCotent = json_encode([
            'error' => [
                'message' => $message,
                'code' => "{$statusCode}000"
            ]
        ], JSON_THROW_ON_ERROR);
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn($bodyCotent);
        $body->method('__toString')->willReturn($bodyCotent);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($body);

        return $response;
    }

    public function createSendRequestCallback(): \Closure
    {
        return function (RequestInterface $request) {
            parse_str(str_replace('to=', 'to[]=', $request->getUri()->getQuery()), $params);
            $languages = array_map(static fn ($value) => Language::from($value), $params['to']);
            $messages = array_column(
                json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR),
                'Text'
            );
            return $this->create($languages, $messages);
        };
    }

    private function createMock(string $className): MockObject
    {
        return (new MockBuilder($this->test, $className))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock();
    }

    private function content(array $languages, array $messages): string
    {
        $content = [];
        foreach (array_values($messages) as $position => $message) {
            $messageTranslations = [];
            foreach ($languages as $language) {
                /** @var Language $language */
                $messageTranslations[] = [
                    'text' => call_user_func($this->translateBehaviour, $language, $message, $position),
                    'to' => $language,
                ];
            }
            $content[$position] = ['translations' => $messageTranslations];
        }
        return json_encode($content, JSON_THROW_ON_ERROR);
    }
}
