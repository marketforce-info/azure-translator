<?php
declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\MessageFormat;

use MarketforceInfo\AzureTranslator\Exceptions\InvalidArgumentException;

class BasicFormatter implements MessageFormat
{
    public function __construct(
        private readonly string $startChar = '{',
        private readonly string $endChar = '}'
    ) {
        if (strlen($startChar) !== 1 || strlen($endChar) !== 1) {
            throw new InvalidArgumentException('Start and end characters must be a single character');
        }
    }

    public function toAzure(string $message): string
    {
        $pattern = sprintf('/%s(.*?)%s/', preg_quote($this->startChar, '/'), preg_quote($this->endChar, '/'));
        return preg_replace_callback($pattern, function (array $matches) {
            return $this->encode($matches[1]);
        }, $message);
    }

    public function fromAzure(string $content): string
    {
        $pattern = "/<t:var [^>]+>([^<]*)<\/t:var>/";
        return preg_replace_callback($pattern, function (array $matches) {
            return "{$this->startChar}{$matches[1]}{$this->endChar}";
        }, $content);
    }

    private function encode(string $content): string
    {
        $content = htmlentities($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
        return "<t:var class=\"notranslate\">$content</t:var>";
    }
}
