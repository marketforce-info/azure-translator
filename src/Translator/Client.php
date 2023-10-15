<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\Translator;

use Generator;
use JsonException;
use MarketforceInfo\AzureTranslator\Exceptions\ClientException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class Client
{
    /** @var callable|null  */
    private $traceIdCallback;

    private array $meteredUsage = [];

    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactory $requestFactory,
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
        $clientTraceId = $this->createClientTraceId();
        $request = $this->requestFactory->create($messages, $clientTraceId);
        $messages->clearSources();

        $results = $this->transfer($request);
        foreach ($results as $position => $result) {
            foreach ($result['translations'] as $languageTranslation) {
                $languageTranslation['clientTraceId'] = $clientTraceId;
                yield $position => $languageTranslation;
            }
        }
    }

    public function getMeteredUsage(): array
    {
        return $this->meteredUsage;
    }

    private function transfer(RequestInterface $request): array
    {
        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw ClientException::create($request, $response);
        }

        $clientTraceId = $request->getHeader('X-ClientTraceId')[0] ?? 'unknown';
        $this->meteredUsage[$clientTraceId] = $response->getHeader('X-Metered-Usage');
        return json_decode(
            (string)$response->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
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
