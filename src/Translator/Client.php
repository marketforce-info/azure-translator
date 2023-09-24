<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\Translator;

use Generator;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    public const BASE_URL_GLOBAL = 'https://api.cognitive.microsofttranslator.com';
    public const BASE_URL_ASIA = 'https://api-apc.cognitive.microsofttranslator.com';
    public const BASE_URL_EUROPE = 'https://api-eur.cognitive.microsofttranslator.com';
    public const BASE_URL_US = 'https://api-nam.cognitive.microsofttranslator.com';

    /** @var callable|null  */
    private $traceIdCallback;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestInterface $baseRequest,
        private readonly StreamFactoryInterface $streamFactory,
        callable $traceIdCallback = null,
    ) {
        $this->traceIdCallback = $traceIdCallback;
    }

    /**
     * @return Generator<array<int, array{to: string, text: string, clientTraceId: string}>>
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function translate(Messages $messages): Generator
    {
        try {
            $jsonContent = json_encode($messages->toArray(), JSON_THROW_ON_ERROR);
            $request = $this->baseRequest
                ->withHeader('X-ClientTraceId', $clientTraceId = $this->createClientTraceId())
                ->withHeader('Content-Length', (string)strlen($jsonContent))
                ->withBody($this->streamFactory->createStream($jsonContent));

            $results = json_decode(
                (string)$this->client->sendRequest($request)->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            foreach ($results as $position => $result) {
                foreach ($result['translations'] as $languageTranslation) {
                    $languageTranslation['clientTraceId'] = $clientTraceId;
                    yield $position => $languageTranslation;
                }
            }
        } catch (ClientExceptionInterface $exception) {
            throw $exception;
        }
    }

    private function createClientTraceId(): string
    {
        $traceIdFn = static function (): string {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
        };

        if (isset($this->traceIdCallback) && is_callable($this->traceIdCallback)) {
            return (string)call_user_func($this->traceIdCallback, $traceIdFn);
        }

        return $traceIdFn();
    }
}
