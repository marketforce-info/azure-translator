<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

use MarketforceInfo\AzureTranslator\Exceptions\RuntimeException;
use MarketforceInfo\AzureTranslator\MessageFormatter\MessageFormatter;
use MarketforceInfo\AzureTranslator\Translator\Delegate;
use MarketforceInfo\AzureTranslator\Translator\Client;
use MarketforceInfo\AzureTranslator\Translator\Language;
use MarketforceInfo\AzureTranslator\Translator\Messages;
use MarketforceInfo\AzureTranslator\Translator\ProfanityHandler;
use MarketforceInfo\AzureTranslator\Translator\Translation;

class Translator
{
    /** @var callable */
    private $onTranslate;
    private Messages $messages;

    public function __construct(
        private readonly Client $client,
        private readonly ?MessageFormatter $messageFormatter = null,
        private readonly ?ProfanityHandler $profanityHandler = null,
        array $config = []
    ) {
        $this->messages = new Messages(
            messageLimit: $config['messageLimit'] ?? Messages::MAX_MESSAGE_LENGTH,
            characterLimit: $config['characterLimit'] ?? Messages::MAX_CHARACTER_LENGTH
        );
    }

    public function onTranslate(callable $callback): self
    {
        $this->onTranslate = $callback;
        return $this;
    }

    public function begin(callable $callback): self
    {
        if (!isset($this->onTranslate)) {
            throw new RuntimeException('Missing required translation callback.');
        }

        $callback(new Delegate(fn (string $message, mixed $state) => $this->translate($message, $state)));
        $this->processMessages();
        return $this;
    }

    /**
     * A list of all calls made using the Client-Trace-Id as a key. Each call records the response header of usage
     * from the Azure Translate service.
     *
     * @return array<string, array<int, string>>
     */
    public function getMeteredUsage(): array
    {
        return $this->client->getMeteredUsage();
    }

    private function translate(string $message, mixed $state): void
    {
        if (isset($this->messageFormatter)) {
            $message = $this->messageFormatter->toAzure($message);
        }
        $this->messages->validate($message);

        if (!$this->messages->canAccept($message)) {
            $this->processMessages();
        }

        $this->messages->add($message, $state);
    }

    private function processMessages(): void
    {
        foreach ($this->client->translate($this->messages) as $position => $translation) {
            $language = Language::tryFrom((string)($translation['to'] ?? null));
            if (!$language instanceof Language) {
                continue;
            }
            call_user_func(
                $this->onTranslate,
                new Translation(
                    $this->filter($translation['text']),
                    $language,
                    $translation['clientTraceId'],
                    $this->messages[$position]
                )
            );
        }
        $this->messages->clear();
    }

    private function filter(string $message): string
    {
        if (isset($this->profanityHandler)) {
            $message = ($this->profanityHandler)($message);
        }
        if (isset($this->messageFormatter)) {
            $message = $this->messageFormatter->fromAzure($message);
        }

        return $message;
    }
}
