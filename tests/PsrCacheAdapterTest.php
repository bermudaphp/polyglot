<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Cache\PsrCacheAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

#[CoversClass(PsrCacheAdapter::class)]
class PsrCacheAdapterTest extends TestCase
{
    private PsrCacheAdapter $adapter;
    private MockObject $mockCache;

    protected function setUp(): void
    {
        $this->mockCache = $this->createMock(CacheInterface::class);
        $this->adapter = new PsrCacheAdapter($this->mockCache, 3600);
    }

    #[Test]
    public function getCallsPsrCacheGet(): void
    {
        $messages = ['welcome' => 'Welcome'];
        $key = 'polyglot.translations.en.messages';

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($messages);

        $result = $this->adapter->get('en', 'messages');
        $this->assertSame($messages, $result);
    }

    #[Test]
    public function setCallsPsrCacheSet(): void
    {
        $messages = ['welcome' => 'Welcome'];
        $key = 'polyglot.translations.en.messages';

        $this->mockCache->expects($this->once())
            ->method('set')
            ->with($key, $messages, 3600);

        $this->adapter->set('en', 'messages', $messages);
    }

    #[Test]
    public function hasCallsPsrCacheHas(): void
    {
        $key = 'polyglot.translations.en.messages';

        $this->mockCache->expects($this->once())
            ->method('has')
            ->with($key)
            ->willReturn(true);

        $result = $this->adapter->has('en', 'messages');
        $this->assertTrue($result);
    }

    #[Test]
    public function clearCallsPsrCacheDelete(): void
    {
        $key = 'polyglot.translations.en.messages';

        $this->mockCache->expects($this->once())
            ->method('delete')
            ->with($key);

        $this->adapter->clear('en', 'messages');
    }

    #[Test]
    public function clearAllCallsPsrCacheClear(): void
    {
        $this->mockCache->expects($this->once())
            ->method('clear');

        $this->adapter->clearAll();
    }

    #[Test]
    public function getNullForNonArrayResult(): void
    {
        $key = 'polyglot.translations.en.messages';

        $this->mockCache->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn('not an array');

        $result = $this->adapter->get('en', 'messages');
        $this->assertNull($result);
    }
}