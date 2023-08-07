<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\MessageFormat;

interface MessageFormat
{
    public function toAzure(string $message): string;
    public function fromAzure(string $content): string;
}
