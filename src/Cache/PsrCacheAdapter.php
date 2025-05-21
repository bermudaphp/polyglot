<?php

namespace Bermuda\Polyglot\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * PSR-16 Compatible cache implementation
 */
class PsrCacheAdapter implements MessageCacheInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(string $locale, string $domain): ?array
    {
        $key = $this->getCacheKey($locale, $domain);
        $data = $this->cache->get($key);

        return is_array($data) ? $data : null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $locale, string $domain, array $messages): void
    {
        $key = $this->getCacheKey($locale, $domain);
        $this->cache->set($key, $messages, $this->ttl);
    }

    /**
     * @inheritDoc
     */
    public function has(string $locale, string $domain): bool
    {
        $key = $this->getCacheKey($locale, $domain);
        return $this->cache->has($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(string $locale, string $domain): void
    {
        $key = $this->getCacheKey($locale, $domain);
        $this->cache->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clearAll(): void
    {
        $this->cache->clear();
    }

    private function getCacheKey(string $locale, string $domain): string
    {
        return "polyglot.translations.$locale.$domain";
    }
}
