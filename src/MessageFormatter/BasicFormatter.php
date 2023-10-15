<?php

declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\MessageFormatter;

class BasicFormatter implements MessageFormatter
{
    public const TAG_VAR_OPEN = '<t:var class="notranslate">';

    public const TAG_VAR_CLOSE = '</t:var>';

    public function __construct(
        private readonly string $startToken = '{',
        private readonly string $endToken = '}'
    ) {
    }

    public function toAzure(string $message): string
    {
        $pattern = sprintf('/%s(.*?)%s/', preg_quote($this->startToken, '/'), preg_quote($this->endToken, '/'));
        return preg_replace_callback($pattern, function (array $matches) {
            return $this->encode($matches[1]);
        }, $message);
    }

    public function fromAzure(string $content): string
    {
        $pattern = "/<t:var [^>]+>([^<]*)<\/t:var>/";
        return preg_replace_callback($pattern, function (array $matches) {
            $matches[1] = html_entity_decode($matches[1], ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
            return "{$this->startToken}{$matches[1]}{$this->endToken}";
        }, $content);
    }

    private function encode(string $content): string
    {
        $content = htmlentities($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
        return self::TAG_VAR_OPEN . $content . self::TAG_VAR_CLOSE;
    }
}
