<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Formatter\DefaultMessageFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultMessageFormatter::class)]
class DefaultMessageFormatterTest extends TestCase
{
    private DefaultMessageFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new DefaultMessageFormatter();
    }

    #[Test]
    public function formatsNamedParameters(): void
    {
        $message = 'Hello, {name}!';
        $result = $this->formatter->format($message, ['name' => 'John']);

        $this->assertSame('Hello, John!', $result);
    }

    #[Test]
    public function formatsMultipleParameters(): void
    {
        $message = 'Hello, {name}! Today is {day}.';
        $result = $this->formatter->format($message, [
            'name' => 'John',
            'day' => 'Monday'
        ]);

        $this->assertSame('Hello, John! Today is Monday.', $result);
    }

    #[Test]
    public function formatsPositionalParameters(): void
    {
        $message = 'The {0} jumped over the {1}.';
        $result = $this->formatter->format($message, [
            0 => 'fox',
            1 => 'fence'
        ]);

        $this->assertSame('The fox jumped over the fence.', $result);
    }

    #[Test]
    public function handlesNonExistentParameters(): void
    {
        $message = 'Hello, {name}! Today is {day}.';
        $result = $this->formatter->format($message, ['name' => 'John']);

        $this->assertSame('Hello, John! Today is {day}.', $result);
    }

    #[Test]
    public function handlesEmptyParameters(): void
    {
        $message = 'Hello, {name}!';
        $result = $this->formatter->format($message, []);

        $this->assertSame('Hello, {name}!', $result);
    }

    #[Test]
    public function handlesNullParameter(): void
    {
        $message = 'Value: {value}';
        $result = $this->formatter->format($message, ['value' => null]);

        $this->assertSame('Value: ', $result);
    }

    #[Test]
    public function handlesScalarParameters(): void
    {
        $message = 'Values: {string}, {int}, {float}, {bool}';
        $result = $this->formatter->format($message, [
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'bool' => true
        ]);

        $this->assertSame('Values: text, 42, 3.14, 1', $result);
    }
}