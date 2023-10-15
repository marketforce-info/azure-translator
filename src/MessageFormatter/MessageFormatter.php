<?php

declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\MessageFormatter;

interface MessageFormatter
{
    public function toAzure(string $message): string;

    public function fromAzure(string $content): string;
}
