<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

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
        callable $onTranslate,
        private readonly ?MessageFormatter $messageFormatter = null,
        private readonly ?ProfanityHandler $profanityHandler = null,
        array $config = []
    ) {
        $this->onTranslate = $onTranslate;
        $this->messages = new Messages(
            messageLimit: $config['messageLimit'] ?? Messages::MAX_MESSAGE_LENGTH,
            characterLimit: $config['characterLimit'] ?? Messages::MAX_CHARACTER_LENGTH
        );
    }

    public function begin(callable $callback): self
    {
        $callback(new Delegate(fn (string $message, array $state = []) => $this->translate($message, $state)));
        $this->processMessages();
        return $this;
    }

    private function translate(string $message, array $state = []): self
    {
        $message = $this->messageFormatter->toAzure($message);
        $this->messages->validate($message);

        if (!$this->messages->canAccept($message)) {
            $this->processMessages();
        }

        $this->messages->add($message, $state);
        return $this;
    }

    private function processMessages(): void
    {
        foreach ($this->client->translate($this->messages) as $position => $translation) {
            $language = Language::tryFrom((string)($translation['to'] ?? null));
            if (!$language instanceof Language) {
                continue;
            }
            [, $state] = $this->messages[$position];
            ($this->onTranslate)(new Translation(
                $this->filter($translation['text']),
                $language,
                $translation['clientTraceId'],
                $state
            ));
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
