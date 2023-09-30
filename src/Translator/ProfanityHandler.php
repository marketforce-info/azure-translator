<?php

namespace MarketforceInfo\AzureTranslator\Translator;

class ProfanityHandler
{
    public const ACTION_MARKED = 'Marked';
    public const ACTION_DELETED = 'Deleted';
    public const MARKER_ASTERISK = 'Asterisk';
    public const MARKER_TAG = 'Tag';

    /** @var ?callable $marker */
    private $marker;

    public function __construct(private readonly string $action, ?callable $marker = null)
    {
        if ($this->action === self::ACTION_MARKED) {
            $this->marker = $marker;
        }
    }

    public function __invoke(string $message): string
    {
        return ($this->action === self::ACTION_MARKED && is_callable($this->marker))
            ? $this->tag($message)
            : $message;
    }

    private function tag(string $message): string
    {
        return preg_replace_callback(
            '/<profanity>([^<]+)<\/profanity>/',
            fn (array $matches) => call_user_func($this->marker, $matches[1]),
            $message
        );
    }
}
