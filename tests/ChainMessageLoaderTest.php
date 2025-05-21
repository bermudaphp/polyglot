<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Loader\ChainMessageLoader;
use Bermuda\Polyglot\Loader\JsonFileMessageLoader;
use Bermuda\Polyglot\Loader\PhpArrayMessageLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ChainMessageLoader::class)]
class ChainMessageLoaderTest extends TestCase
{
    private ChainMessageLoader $chainLoader;
    private MockObject $loader1;
    private MockObject $loader2;

    protected function setUp(): void
    {
        $this->loader1 = $this->createMock(JsonFileMessageLoader::class);
        $this->loader2 = $this->createMock(PhpArrayMessageLoader::class);

        $this->chainLoader = new ChainMessageLoader([$this->loader1, $this->loader2]);
    }

    #[Test]
    public function usesFirstLoaderThatHasMessages(): void
    {
        $messages = ['welcome' => 'Welcome'];

        $this->loader1->method('exists')
            ->with('en', 'messages')
            ->willReturn(true);

        $this->loader1->method('load')
            ->with('en', 'messages')
            ->willReturn($messages);

        $this->loader2->expects($this->never())
            ->method('exists');

        $this->loader2->expects($this->never())
            ->method('load');

        $result = $this->chainLoader->load('en', 'messages');
        $this->assertSame($messages, $result);
    }

    #[Test]
    public function movesToNextLoaderIfFirstDoesNotHaveMessages(): void
    {
        $messages = ['welcome' => 'Welcome'];

        $this->loader1->method('exists')
            ->with('en', 'messages')
            ->willReturn(false);

        $this->loader1->expects($this->never())
            ->method('load');

        $this->loader2->method('exists')
            ->with('en', 'messages')
            ->willReturn(true);

        $this->loader2->method('load')
            ->with('en', 'messages')
            ->willReturn($messages);

        $result = $this->chainLoader->load('en', 'messages');
        $this->assertSame($messages, $result);
    }

    #[Test]
    public function returnsEmptyArrayIfNoLoaderHasMessages(): void
    {
        $this->loader1->method('exists')
            ->with('en', 'messages')
            ->willReturn(false);

        $this->loader2->method('exists')
            ->with('en', 'messages')
            ->willReturn(false);

        $result = $this->chainLoader->load('en', 'messages');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function existsReturnsTrueIfAnyLoaderHasMessages(): void
    {
        $this->loader1->method('exists')
            ->with('en', 'messages')
            ->willReturn(false);

        $this->loader2->method('exists')
            ->with('en', 'messages')
            ->willReturn(true);

        $this->assertTrue($this->chainLoader->exists('en', 'messages'));
    }

    #[Test]
    public function existsReturnsFalseIfNoLoaderHasMessages(): void
    {
        $this->loader1->method('exists')
            ->with('en', 'messages')
            ->willReturn(false);

        $this->loader2->method('exists')
            ->with('en', 'messages')
            ->willReturn(false);

        $this->assertFalse($this->chainLoader->exists('en', 'messages'));
    }

    #[Test]
    public function canAddLoaderToChain(): void
    {
        $loader3 = $this->createMock(JsonFileMessageLoader::class);
        $this->chainLoader->addLoader($loader3);

        // Test that the new loader was added by checking it's used when others return false
        $messages = ['welcome' => 'Welcome'];

        $this->loader1->method('exists')->willReturn(false);
        $this->loader2->method('exists')->willReturn(false);

        $loader3->method('exists')
            ->with('en', 'messages')
            ->willReturn(true);

        $loader3->method('load')
            ->with('en', 'messages')
            ->willReturn($messages);

        $result = $this->chainLoader->load('en', 'messages');
        $this->assertSame($messages, $result);
    }
}