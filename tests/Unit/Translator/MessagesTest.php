<?php

namespace MarketforceInfo\AzureTranslator\Tests\Unit\Translator;

use MarketforceInfo\AzureTranslator\Exceptions\BadMethodCallException;
use MarketforceInfo\AzureTranslator\Exceptions\InvalidArgumentException;
use MarketforceInfo\AzureTranslator\Translator\Messages;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MarketforceInfo\AzureTranslator\Translator\Messages
 */
class MessagesTest extends TestCase
{
    public function testImplements()
    {
        $messages = new Messages();
        $this->assertInstanceOf(\Countable::class, $messages);
        $this->assertInstanceOf(\ArrayAccess::class, $messages);
        $this->assertIsIterable($messages);
    }

    public function testAddingMessage()
    {
        $messages = new Messages();
        $messages->add('Hello');
        $this->assertCount(1, $messages);
    }

    public function testAddingMultibyteCharacters()
    {
        $messages = new Messages();
        $messages->add('こんにちは');
        $this->assertCount(1, $messages);
    }

    public function testAddingMultipleMessages()
    {
        $messages = new Messages();
        $messages->add('Foo');
        $messages->add('Bar');
        $messages->add('Baz');
        $this->assertCount(3, $messages);
    }

    public function testAddingMessageWithState()
    {
        $messages = new Messages();
        $messages->add('Hello', ['foo' => 'bar']);
        $this->assertCount(1, $messages);
    }

    public function testAccessingPositional()
    {
        $messages = new Messages();
        $messages->add('Hello');
        $this->assertEquals('Hello', $messages[0][0]);
    }

    public function testAccessingPositionalState()
    {
        $messages = new Messages();
        $messages->add('Hello', ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $messages[0][1]);
    }

    public function testSettingMessageLimit()
    {
        $this->expectException(BadMethodCallException::class);
        $messages = new Messages(messageLimit: 2);
        $messages->add('Foo');
        $messages->add('Bar');
        $messages->add('Baz');
    }

    public function testSettingCharacterLimit()
    {
        $this->expectException(InvalidArgumentException::class);
        $messages = new Messages(characterLimit: 100);
        $messages->add(str_pad('', 101, 'a'));
    }

    public function testValidatingMessage()
    {
        $messages = new Messages(characterLimit: 100);
        $messages->validate(str_pad('', 100, 'a'));
        $this->assertTrue(true);
    }

    public function testValidatingInvalidMessage()
    {
        $this->expectException(InvalidArgumentException::class);
        $messages = new Messages(characterLimit: 100);
        $messages->validate(str_pad('', 101, 'a'));
    }

    public function testCanAcceptByCharacterLimit()
    {
        $messages = new Messages(messageLimit: 1000, characterLimit: 100);
        $this->assertTrue($messages->canAccept($initial = str_pad('', 99, 'a')));
        $messages->add($initial);
        $this->assertFalse($messages->canAccept(str_pad('', 2, 'a')));
    }

    public function testCanAcceptByMessageLimit()
    {
        $messages = new Messages(messageLimit: 1, characterLimit: 1000);
        $this->assertTrue($messages->canAccept($initial = str_pad('', 100, 'a')));
        $messages->add($initial);
        $this->assertFalse($messages->canAccept(str_pad('', 100, 'a')));
    }

    public function testClear()
    {
        $messages = new Messages();
        $messages->add('Foo');
        $this->assertCount(1, $messages);
        $messages->clear();
        $this->assertCount(0, $messages);
    }
}
