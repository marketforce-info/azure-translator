<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

use Generator;
use MarketforceInfo\AzureTranslator\Config\BaseUrl;
use MarketforceInfo\AzureTranslator\Config\Language;
use MarketforceInfo\AzureTranslator\Exceptions\InvalidArgumentException;
use MarketforceInfo\AzureTranslator\Exceptions\OutOfRangeException;
use MarketforceInfo\AzureTranslator\MessageFormat\MessageFormat;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Translator
{
    public const MAX_MESSAGE_LENGTH = 1000;
    public const MAX_CHARACTER_LENGTH = 50_000;

    /** @var callable */
    private $onTranslate;
    private ?MessageFormat $formatter;
    private string $fromLanguage = 'en';
    private array $toLanguages;
    private array $config = [
        'message_limit' => self::MAX_MESSAGE_LENGTH,
        'character_limit' => self::MAX_CHARACTER_LENGTH,
    ];

    private StreamFactoryInterface $streamFactory;

    private array $translations = [];
    private int $characterCount = 0;

    public function __construct(
        private readonly HttpClient $client,
        private readonly RequestFactoryInterface $requestFactory,
        array $languages,
        callable $onTranslate,
        array $config = []
    ) {
        $this->onTranslate = $onTranslate;
        $this->toLanguages = $languages;

        $config = array_merge($this->config, $config);
        foreach ($config as $key => $value) {
            $method = 'set' . str_replace(' ', '', ucfirst(str_replace('_', ' ', $key)));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    public function setSubscriptionKey(string $value): self
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Subscription key cannot be empty');
        }
        $this->config['subscription_key'] = $value;
        return $this;
    }

    public function setSubscriptionRegion(string $value): self
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Subscription region cannot be empty');
        }
        $this->config['subscription_region'] = $value;
        return $this;
    }

    public function setMessageFormatter(MessageFormat $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    public function setMessageLimit(int $value): self
    {
        $range = ['min_range' => 1, 'max_range' => self::MAX_MESSAGE_LENGTH];
        if (!filter_var($value, FILTER_VALIDATE_INT, ['options' => $range])) {
            throw new OutOfRangeException('Batch size must be between 1 and 1000');
        }
        $this->config['message_limit'] = $value;
        return $this;
    }

    public function setCharacterLimit(int $value): self
    {
        $range = ['min_range' => 1, 'max_range' => self::MAX_CHARACTER_LENGTH];
        if (!filter_var($value, FILTER_VALIDATE_INT, ['options' => $range])) {
            throw new OutOfRangeException('Batch size must be between 1 and 50,000');
        }
        $this->config['character_limit'] = $value;
        return $this;
    }

    public function session(callable $callback): self
    {
        if (!isset($this->config['subscription_key'], $this->config['subscription_region'])) {
            throw new InvalidArgumentException('Subscription key and region config must be set');
        }

        $callback($this);
        $this->doTranslate();
        return $this;
    }

    public function translate(string $message, array $state = []): self
    {
        try {
            if (mb_strlen($message) > $this->config['character_limit']) {
                throw new OutOfRangeException(
                    "Message length must be less than {$this->config['character_limit']} characters"
                );
            }
            $messageLength = mb_strlen($message);
            if ($this->characterCount + $messageLength > $this->config['character_limit']) {
                $this->doTranslate();
            }

            $this->translations[] = [$this->prepareMessage($message), $state];
            $this->characterCount += $messageLength;
            if (count($this->translations) === $this->config['message_limit']) {
                $this->doTranslate();
            }
        } catch (ParsingException $exception) {
            foreach ($this->toLanguages as $language) {
                call_user_func($this->onTranslate, ContextError::fromException($exception, $message, $language, $state));
            }
        }
        return $this;
    }

    private function prepareMessage(string $message): string
    {
        if (!isset($this->formatter)) {
            return $message;
        }
        return $this->formatter->toAzure($message);
    }

    private function doTranslate(): void
    {
        foreach ($this->fetch() as $position => $translation) {
            [$originalMessage, $state] = $this->translations[$position];
            $context = new Context($originalMessage, $this->fromLanguage, $state);

            call_user_func($this->onTranslate, $translation['text'], $translation['to'], $context);
        }
        $this->translations = [];
        $this->characterCount = 0;
    }

    /**
     * @return Generator<array<int, array{to: string, text: string, clientTraceId: string}>>
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    private function fetch(): Generator
    {
        try {
            $results = json_decode(
                (string)$this->client->sendRequest($request)->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (ClientExceptionInterface $exception) {
            $response = $exception->getResponse();
            if ($response === null) {
                throw $exception;
            }
            $results = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            foreach ($results['error']['innererror']['details'] ?? [] as $detail) {
                $message = $detail['message'] ?? '';
                $code = $detail['code'] ?? '';
                $target = $detail['target'] ?? '';
                throw new ClientException($message, $code, $target);
            }
            throw $exception;
        }
        foreach ($results as $position => $result) {
            foreach ($result['translation'] ?? [] as $languageTranslation) {
                $languageTranslation['clientTraceId'] = $clientTraceId;
                yield $position => $languageTranslation;
            }
        }
    }

    private function request(): RequestInterface
    {
        $baseUrl = ($this->config['base_url'] ?? BaseUrl::global)->value;
        $params = http_build_query([
            'api-version' => '3.0',
            'from' => $this->fromLanguage,
            'textType' => 'html'
        ]);
        $to = implode('&', array_map(static fn(Language $language) => 'to=' . urlencode($language->value), $this->toLanguages));
        $url = "{$baseUrl}/translate?{$params}&{$to}";

        $body = $this->body();
        $request = $this->requestFactory
            ->createRequest('GET', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Content-Length', strlen($body))
            ->withHeader($this->config['authentication']['header'], $this->config['authentication']['value'])
            ->withHeader('X-ClientTraceId', $clientTraceId = $this->createClientTraceId())
            ->withBody($this->streamFactory->createStream($body));
        if (isset($this->config['subscription_region'])) {
            $request = $request->withHeader('Ocp-Apim-Subscription-Region', $this->config['subscription_region']);
        }
        return $request;
    }

    private function body(): string
    {
        $body = [];
        foreach ($this->translations as $index => $translation) {
            $body[$index] = ['Text' => $translation[0]];
        }

        return json_encode($body, JSON_THROW_ON_ERROR);
    }

    private function createClientTraceId(): string
    {
        if (isset($this->config['trace_id']) && is_callable($this->config['trace_id'])) {
            return call_user_func($this->config['trace_id']);
        }

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
    }
}
