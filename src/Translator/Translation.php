<?php

namespace MarketforceInfo\AzureTranslator\Translator;

class Translation
{
    public function __construct(
        public readonly string $message,
        public readonly Language $language,
        public readonly string $traceId,
        public readonly array $state,
    ) {
    }
}
