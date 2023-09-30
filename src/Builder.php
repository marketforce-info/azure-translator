<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

use MarketforceInfo\AzureTranslator\Exceptions\RuntimeException;
use MarketforceInfo\AzureTranslator\MessageFormatter\BasicFormatter;
use MarketforceInfo\AzureTranslator\MessageFormatter\MessageFormatter;
use MarketforceInfo\AzureTranslator\Translator\Client;
use MarketforceInfo\AzureTranslator\Translator\Language;
use MarketforceInfo\AzureTranslator\Translator\ProfanityHandler;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Builder
{
    private string $baseUrl = Client::BASE_URL_GLOBAL;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private array|null $authentication;
    private Language $fromLanguage = Language::english;
    private array $toLanguages;
    private string $subscriptionRegion;
    private MessageFormatter $formatter;
    /** @var callable */
    private $translateCallback;
    /** @var callable */
    private $traceIdCallback;
    private array $config = [];

    /**
     * Simplest way to create a translator.
     */
    public static function createBasic(string $subscriptionKey, array $languages, callable $translateCallback): Translator
    {
        return (new self())
            ->withSubscriptionKey($subscriptionKey)
            ->withLanguages($languages)
            ->withTranslateCallback($translateCallback)
            ->create();
    }

    /**
     * Create a translator with message formatting. If a message formatter is not provided, a basic formatter will be
     * used. This will handle curly braces in the message.
     */
    public static function createWithMessageFormat(
        string $subscriptionKey,
        array $languages,
        callable $translateCallback,
        MessageFormatter $formatter = null
    ): Translator {
        if (!$formatter) {
            $formatter = new BasicFormatter();
        }
        return (new self())
            ->withSubscriptionKey($subscriptionKey)
            ->withLanguages($languages)
            ->withTranslateCallback($translateCallback)
            ->withMessageFormatter($formatter)
            ->create();
    }

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
     * @param Language $from
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
        $this->authentication = ['Ocp-Apim-Subscription-Key', $value];
        return $this;
    }

    /**
     * Set authentication to use a bearer token.
     */
    public function withBearerToken(string $value): self
    {
        $this->authentication = ['Authorization', 'Bearer: ' . $value];
        return $this;
    }

    /**
     * Set the subscription region.
     */
    public function withSubscriptionRegion(string $value): self
    {
        $this->subscriptionRegion = $value;
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
     * Sets callback to handle the translation. Required.
     */
    public function withTranslateCallback(callable $callback): self
    {
        $this->translateCallback = $callback;
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
        unset($this->config['profanity_handling']);
        return $this;
    }

    /**
     * Sets so the behaviour of the translator is to delete phrases identified as profanity.
     */
    public function withProfanityDeleted(): self
    {
        $this->config['profanity_handling'] = [
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
        $this->config['profanity_handling'] = [
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
                $this->baseRequest(),
                $this->streamFactory,
                $this->traceIdCallback ?? null
            ),
            $this->translateCallback,
            $this->formatter ?? null,
            $this->profanityHandler ?? null,
        );
    }

    private function baseRequest(): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->baseUrl)
            ->withHeader('Content-Type', 'application/json');

        $params = [
            'api-version' => '3.0',
            'from' => $this->fromLanguage->value,
            'textType' => 'html',
            'profanityAction' => 'NoAction',
        ];
        if (isset($this->config['profanity_handling'])) {
            $params['profanityAction'] = $this->config['profanity_handling']['action'];
            if ($this->config['profanity_handling']['action'] === ProfanityHandler::ACTION_MARKED) {
                $params['profanityMarker'] = (is_callable($this->config['profanity_handling']['marker']))
                    ? ProfanityHandler::MARKER_TAG
                    : ProfanityHandler::MARKER_ASTERISK;
            }
        }

        $uri = $request->getUri();
        $request = $request->withUri(
            $uri->withScheme('https')
                ->withPath('/translate')
                ->withQuery($params . '&' . Language::asQueryParam($this->toLanguages))
        );

        if (isset($this->authentication)) {
            $request = $request->withHeader(...$this->authentication);
        }
        if (isset($this->subscriptionRegion)) {
            $request = $request->withHeader('Ocp-Apim-Subscription-Region', $this->subscriptionRegion);
        }
        if (isset($this->resourceId)) {
            $request = $request->withHeader('Ocp-Apim-ResourceId', $this->resourceId);
        }

        return $request;
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
        if (!isset($this->translateCallback)) {
            throw new RuntimeException('Translate callback must be set.');
        }
    }
}
