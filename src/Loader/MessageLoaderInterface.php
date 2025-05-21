<?php

namespace Bermuda\Polyglot\Loader;

use Bermuda\Polyglot\LocaleEnum;

/**
 * Interface for message loaders
 */
interface MessageLoaderInterface
{
    /**
     * Load messages for a specific locale and domain
     *
     * @return array<string, mixed>
     */
    public function load(string|LocaleEnum $locale, string $domain): array;

    /**
     * Check if messages exist for the given locale and domain
     */
    public function exists(string|LocaleEnum $locale, string $domain): bool;
}