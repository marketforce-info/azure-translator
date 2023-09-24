<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\MessageFormatter;

class PassthruFormatter implements MessageFormatter
{

    public function toAzure(string $message): string
    {
        return $message;
    }

    public function fromAzure(string $content): string
    {
        return $content;
    }
}
