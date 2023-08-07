<?php

namespace MarketforceInfo\AzureTranslator;

use MarketforceInfo\AzureTranslator\Config\BaseUrl;
use MarketforceInfo\AzureTranslator\MessageFormat\BasicFormatter;
use MarketforceInfo\AzureTranslator\MessageFormat\MessageFormat;

class Builder
{
    private ?Psr18ClientInterface $client;
    private ?Psr17RequestFactoryInterface $requestFactory;
    private array $authentication;
    private string $subscriptionRegion;
    private string $fromLanguage = 'en';
    private array $toLanguages;
    private ?MessageFormat $formatter;

    public static function createBasic(string $subscriptionKey, array $languages): Translator
    {
        return self::base($subscriptionKey, $languages)->create();
    }

    public static function createWithMessageFormat(string $subscriptionKey, array $languages, MessageFormat $formatter = null): Translator
    {
        if (!$formatter) {
            $formatter = new BasicFormatter();
        }
        return self::base($subscriptionKey, $languages)->withMessageFormatter($formatter)->create();
    }

    private static function base(string $subscriptionKey, array $languages): self
    {
        return (new self())
            ->withSubscriptionKeyAuth($subscriptionKey)
            ->withLanguagesTo($languages);
    }

    public function withSubscriptionKeyAuth(string $value): self
    {
        $this->headers['Ocp-Apim-Subscription-Key'] = $value;
        unset($this->headers['Authorisation']);
        return $this;
    }

    public function withBearerTokenAuth(string $value): self
    {
        $this->headers['Authorisation'] = 'Bearer: ' . $value;
        unset($this->headers['Ocp-Apim-Subscription-Key']);
        return $this;
    }

    public function withSubscriptionRegion(string $value): self
    {
        $this->subscriptionRegion = $value;
        return $this;
    }

    public function withClient(Psr18ClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function withRequestFactory(Psr17RequestFactoryInterface $requestFactory): self
    {
        $this->requestFactory = $requestFactory;
        return $this;
    }

    public function withBaseUrl(BaseUrl $baseUrl): self
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function withLanguagesTo(array $languages): self
    {
        $this->toLanguages = $languages;
        return $this;
    }

    public function withLanguageFrom(string $language): self
    {
        $this->fromLanguage = $language;
        return $this;
    }

    public function withMessageFormatter(MessageFormat $formatter): self
    {
        $this->formatter = $formatter;
        return $this;
    }

    public function withProfanityHandlingEnabled()
    {
        $this->config['profanity_handling'] = true;
        return $this;
    }

    public function withProfanityHandlingDisabled()
    {
        $this->config['profanity_handling'] = false;
        return $this;
    }

    public function create(): Translator
    {
        $config = [
            'base_url' => $this->baseUrl->value,
            'from_language' => $this->fromLanguage,
        ];
        if ($this->authentication) {
            [$type, $value] = $this->authentication;
            $config['auth'] = [$type, $value];
        }
        if ($this->subscriptionRegion) {
            $config['subscription_region'] = $this->subscriptionRegion;
        }
        if ($this->formatter) {
            $config['message_formatter'] = $this->formatter;
        }

        return new Translator(
            $this->client ?? Psr18ClientDiscovery::find(),
            $this->requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            $this->toLanguages,
            $this->translateCallback,
            $config
        );
    }
}
