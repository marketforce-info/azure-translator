<?php

declare(strict_types=1);

namespace MarketforceInfo\AzureTranslator\Translator;

use MarketforceInfo\AzureTranslator\Exceptions\BadMethodCallException;
use MarketforceInfo\AzureTranslator\Exceptions\InvalidArgumentException;
use Traversable;

class Messages implements \IteratorAggregate, \Countable, \ArrayAccess, \JsonSerializable
{
    public const MAX_MESSAGE_LENGTH = 1000;

    public const MAX_CHARACTER_LENGTH = 50_000;

    private int $messageLimit;

    private int $characterLimit;

    private array $messages = [];

    private int $messageCount = 0;

    private int $characterCount = 0;

    public function __construct(int $messageLimit = self::MAX_MESSAGE_LENGTH, int $characterLimit = self::MAX_CHARACTER_LENGTH)
    {
        $range = [
            'min_range' => 1,
            'max_range' => self::MAX_MESSAGE_LENGTH,
        ];
        if (!filter_var($messageLimit, FILTER_VALIDATE_INT, [
            'options' => $range,
        ])) {
            throw new InvalidArgumentException('Batch size must be between 1 and 1000');
        }
        $this->messageLimit = $messageLimit;

        $range = [
            'min_range' => 1,
            'max_range' => self::MAX_CHARACTER_LENGTH,
        ];
        if (!filter_var($characterLimit, FILTER_VALIDATE_INT, [
            'options' => $range,
        ])) {
            throw new InvalidArgumentException('Batch size must be between 1 and 50,000');
        }
        $this->characterLimit = $characterLimit;
    }

    public function validate(string $message): void
    {
        if (mb_strlen($message) > $this->characterLimit) {
            throw new InvalidArgumentException(
                "Message length must be less than {$this->characterLimit} characters"
            );
        }
    }

    public function add($message, mixed $state): self
    {
        $this->validate($message);
        if (!$this->canAccept($message)) {
            throw new BadMethodCallException('Message queue requires processing');
        }

        $this->messages[] = [$message, $state];
        $this->messageCount++;
        $this->characterCount += mb_strlen($message);
        return $this;
    }

    public function canAccept(string $message): bool
    {
        return ($this->characterCount + mb_strlen($message)) <= $this->characterLimit
            && ($this->messageCount + 1) <= $this->messageLimit;
    }

    public function clear(): self
    {
        $this->messages = [];
        $this->messageCount = 0;
        $this->characterCount = 0;
        return $this;
    }

    public function clearSources(): self
    {
        array_walk($this->messages, static fn (&$message) => $message[0] = '');
        gc_collect_cycles();
        return $this;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->messages);
    }

    public function count(): int
    {
        return $this->messageCount;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->messages[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_int($offset) || !isset($this->messages[$offset])) {
            throw new InvalidArgumentException("Invalid offset {$offset} specified.");
        }
        return $this->messages[$offset][1];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('Cannot set messages');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('Cannot unset messages');
    }

    public function jsonSerialize(): array
    {
        return array_map(static fn (array $message) => [
            'Text' => $message[0],
        ], $this->messages);
    }
}
