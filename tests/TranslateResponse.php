<?php

namespace MarketforceInfo\AzureTranslator\Tests;

use MarketforceInfo\AzureTranslator\Translator\Language;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class TranslateResponse
{
    public function __construct(private TestCase $test)
    {
    }

    public function create(array $languages, array $messages): ResponseInterface|MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($this->content($languages, $messages));
        $response->method('getBody')->willReturn($stream);
        return $response;
    }

    public function createFailure(int $statusCode): ResponseInterface|MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        return $response;
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
                    'text' => $message . ucfirst($language->value),
                    'to' => $language,
                ];
            }
            $content[$position] = ['translations' => $messageTranslations];
        }
        return json_encode($content, JSON_THROW_ON_ERROR);
    }
}
