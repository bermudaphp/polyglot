<?php

namespace Bermuda\Polyglot\Cache;

/**
 * Interface for message cache providers
 */
interface MessageCacheInterface
{
    /**
     * Get cached messages
     *
     * @return array<string, mixed>|null
     */
    public function get(string $locale, string $domain): ?array;

    /**
     * Store messages in cache
     *
     * @param array<string, mixed> $messages
     */
    public function set(string $locale, string $domain, array $messages): void;

    /**
     * Check if cached messages exist
     */
    public function has(string $locale, string $domain): bool;

    /**
     * Clear cache for a specific locale and domain
     */
    public function clear(string $locale, string $domain): void;

    /**
     * Clear all cached messages
     */
    public function clearAll(): void;
}