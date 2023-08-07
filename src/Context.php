<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

class Context
{
    public function __construct(
        public readonly string $message,
        public readonly string $language,
        public readonly string $clientTraceId,
        public readonly array $state = [],
    ) {
    }
}
