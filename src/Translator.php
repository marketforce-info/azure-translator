<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

use MarketforceInfo\AzureTranslator\MessageFormatter\MessageFormatter;
use MarketforceInfo\AzureTranslator\MessageFormatter\PassthruFormatter;
use MarketforceInfo\AzureTranslator\Translator\Delegate;
use MarketforceInfo\AzureTranslator\Translator\Client;
use MarketforceInfo\AzureTranslator\Translator\Language;
use MarketforceInfo\AzureTranslator\Translator\Messages;
use MarketforceInfo\AzureTranslator\Translator\Translation;

class Translator
{
    /** @var callable */
    private $onTranslate;
    private MessageFormatter $formatter;
    private Messages $messages;

    public function __construct(
        private readonly Client $client,
        callable $onTranslate,
        ?MessageFormatter $formatter = null,
        array $config = []
    ) {
        $this->onTranslate = $onTranslate;
        if (!$formatter instanceof MessageFormatter) {
            $formatter = new PassthruFormatter();
        }
        $this->formatter = $formatter;
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
        $message = $this->formatter->toAzure($message);
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
                $this->formatter->fromAzure($translation['text']),
                $language,
                $translation['clientTraceId'],
                $state
            ));
        }
        $this->messages->clear();
    }
}
