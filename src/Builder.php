<?php

declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

use MarketforceInfo\AzureTranslator\Exceptions\RuntimeException;
use MarketforceInfo\AzureTranslator\MessageFormatter\MessageFormatter;
use MarketforceInfo\AzureTranslator\Translator\Client;
use MarketforceInfo\AzureTranslator\Translator\Language;
use MarketforceInfo\AzureTranslator\Translator\ProfanityHandler;
use MarketforceInfo\AzureTranslator\Translator\RequestFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Builder
{
    private string $baseUrl = RequestFactory::BASE_URL_GLOBAL;

    private ClientInterface $client;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private Language $fromLanguage = Language::english;

    private array $toLanguages;

    private MessageFormatter $formatter;

    /**
     * @var callable
     */
    private $traceIdCallback;

    private array $requestConfig = [];

    /**
     * Set the base URL to use. By default, this is the global endpoint.
     *
     * Typical values are:
     * - https://api.cognitive.microsofttranslator.com/ default
     * - https://api-nam.cognitive.microsofttranslator.com/
     * - https://api-eur.cognitive.microsofttranslator.com/
     * - https://api-apc.cognitive.microsofttranslator.com/
     *
     * @see Client::BASE_URL_* constants
     */
    public function withBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Set the client, request factory and stream factory to use.
     */
    public function withHttp(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ): self {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        return $this;
    }

    /**
     * Set the language to translate from. By default, this is English.
     *
     * @param array<int, Language> $to
     * @return $this
     */
    public function withLanguages(array $to, Language $from = Language::english): self
    {
        Language::verify($to, $from);
        $this->toLanguages = $to;
        $this->fromLanguage = $from;
        return $this;
    }

    /**
     * Set authentication to use a subscription key.
     */
    public function withSubscriptionKey(string $value): self
    {
        $this->requestConfig['authentication'] = ['Ocp-Apim-Subscription-Key', $value];
        return $this;
    }

    /**
     * Set authentication to use a bearer token.
     */
    public function withBearerToken(string $value): self
    {
        $this->requestConfig['authentication'] = ['Authorization', 'Bearer: ' . $value];
        return $this;
    }

    /**
     * Set the subscription region.
     */
    public function withSubscriptionRegion(string $value): self
    {
        $this->requestConfig['subscription_region'] = $value;
        return $this;
    }

    /**
     * Set the resource ID.
     */
    public function withResourceId(string $value): self
    {
        $this->requestConfig['resource_id'] = $value;
        return $this;
    }

    /**
     * Allows for handling the translation message to and from the translator.
     */
    public function withMessageFormatter(MessageFormatter $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    /**
     * Sets callback to generate a trace ID. If not provided, a random UUID will be used.
     */
    public function withTraceIdCallback(callable $callback): self
    {
        $this->traceIdCallback = $callback;
        return $this;
    }

    /**
     * Sets no profanity handling on the translation. This is the default behaviour.
     */
    public function withoutProfanityHandling(): self
    {
        unset($this->requestConfig['profanity_handling']);
        return $this;
    }

    /**
     * Sets so the behaviour of the translator is to delete phrases identified as profanity.
     */
    public function withProfanityDeleted(): self
    {
        $this->requestConfig['profanity_handling'] = [
            'action' => ProfanityHandler::ACTION_DELETED,
            'marker' => null,
        ];
        return $this;
    }

    /**
     * Sets so the behaviour of the translator is to replace phrases identified as profanity with asterisks. If a
     * callable is provided, it will be called with the phrase as the first argument.
     */
    public function withProfanityMarked(callable $handler = null): self
    {
        $this->requestConfig['profanity_handling'] = [
            'action' => ProfanityHandler::ACTION_MARKED,
            'marker' => $handler,
        ];
        return $this;
    }

    /**
     * Allows for using the builder for conditional configuration.
     */
    public function when(bool $match, \Closure $thenCallback, \Closure $elseCallback = null): self
    {
        if ($match) {
            $thenCallback($this);
        } elseif ($elseCallback) {
            $elseCallback($this);
        }
        return $this;
    }

    /**
     * Create a translator instance.
     */
    public function create(): Translator
    {
        $this->verifyRequired();

        return new Translator(
            new Client(
                $this->client,
                new RequestFactory(
                    $this->requestFactory,
                    $this->streamFactory,
                    $this->toLanguages,
                    $this->fromLanguage,
                    $this->baseUrl,
                    $this->requestConfig
                ),
                $this->traceIdCallback ?? null
            ),
            $this->formatter ?? null,
            $this->profanityHandler(),
        );
    }

    private function verifyRequired(): void
    {
        if (!isset($this->client)) {
            throw new RuntimeException('Client must be set.');
        }
        if (!isset($this->requestFactory)) {
            throw new RuntimeException('Request factory must be set.');
        }
        if (!isset($this->streamFactory)) {
            throw new RuntimeException('Stream factory must be set.');
        }
        if (!isset($this->toLanguages)) {
            throw new RuntimeException('Languages must be set.');
        }
    }

    private function profanityHandler(): ?ProfanityHandler
    {
        if (!isset($this->requestConfig['profanity_handling'])) {
            return null;
        }

        return new ProfanityHandler(
            $this->requestConfig['profanity_handling']['action'],
            $this->requestConfig['profanity_handling']['marker']
        );
    }
}
