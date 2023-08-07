<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator;

use Exception;

class ContextError extends Context
{
    public readonly Exception $exception;

    public static function fromException(
        Exception $exception,
        string $message,
        string $language,
        string $clientTraceId,
        array $state = []
    ): self {
        $context = new self($message, $language, $clientTraceId, $state);
        $context->exception = $exception;
        return $context;
    }
}
