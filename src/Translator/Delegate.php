<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\Translator;

class Delegate
{
    public function __construct(private readonly \Closure $translator)
    {
    }

    public function translate(string $message, mixed $state = null): self
    {
        ($this->translator)($message, $state);
        return $this;
    }
}
