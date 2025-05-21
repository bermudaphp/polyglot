<?php

namespace Bermuda\Polyglot\Cache;

/**
 * In-memory cache implementation
 */
class InMemoryCache implements MessageCacheInterface
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $cache = [];

    /**
     * @inheritDoc
     */
    public function get(string $locale, string $domain): ?array
    {
        return $this->cache[$locale][$domain] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function set(string $locale, string $domain, array $messages): void
    {
        $this->cache[$locale][$domain] = $messages;
    }

    /**
     * @inheritDoc
     */
    public function has(string $locale, string $domain): bool
    {
        return isset($this->cache[$locale][$domain]);
    }

    /**
     * @inheritDoc
     */
    public function clear(string $locale, string $domain): void
    {
        unset($this->cache[$locale][$domain]);
    }

    /**
     * @inheritDoc
     */
    public function clearAll(): void
    {
        $this->cache = [];
    }
}