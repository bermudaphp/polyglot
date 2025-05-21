<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Cache\InMemoryCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryCache::class)]
class InMemoryCacheTest extends TestCase
{
    private InMemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache();
    }

    #[Test]
    public function setAndGetWorkCorrectly(): void
    {
        $messages = ['welcome' => 'Welcome'];

        $this->cache->set('en', 'messages', $messages);
        $result = $this->cache->get('en', 'messages');

        $this->assertSame($messages, $result);
    }

    #[Test]
    public function hasReturnsTrueForExistingCache(): void
    {
        $messages = ['welcome' => 'Welcome'];

        $this->cache->set('en', 'messages', $messages);
        $this->assertTrue($this->cache->has('en', 'messages'));
    }

    #[Test]
    public function hasReturnsFalseForNonExistingCache(): void
    {
        $this->assertFalse($this->cache->has('en', 'messages'));
    }

    #[Test]
    public function getReturnsNullForNonExistingCache(): void
    {
        $this->assertNull($this->cache->get('en', 'messages'));
    }

    #[Test]
    public function clearRemovesSpecificCache(): void
    {
        $this->cache->set('en', 'messages', ['welcome' => 'Welcome']);
        $this->cache->set('fr', 'messages', ['welcome' => 'Bienvenue']);

        $this->cache->clear('en', 'messages');

        $this->assertFalse($this->cache->has('en', 'messages'));
        $this->assertTrue($this->cache->has('fr', 'messages'));
    }

    #[Test]
    public function clearAllRemovesAllCache(): void
    {
        $this->cache->set('en', 'messages', ['welcome' => 'Welcome']);
        $this->cache->set('fr', 'messages', ['welcome' => 'Bienvenue']);

        $this->cache->clearAll();

        $this->assertFalse($this->cache->has('en', 'messages'));
        $this->assertFalse($this->cache->has('fr', 'messages'));
    }
}