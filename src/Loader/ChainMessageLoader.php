<?php

namespace Bermuda\Polyglot\Loader;

use Bermuda\Polyglot\Loader;
use Bermuda\Polyglot\LocaleEnum;

/**
 * ChainLoader combines multiple loaders
 */
class ChainMessageLoader implements MessageLoaderInterface
{
    /** @var MessageLoaderInterface[] */
    private array $loaders;

    /**
     * @param MessageLoaderInterface[] $loaders
     */
    public function __construct(array $loaders = [])
    {
        $this->loaders = $loaders;
    }

    /**
     * Add a loader to the chain
     */
    public function addLoader(Loader\MessageLoaderInterface $loader): self
    {
        $this->loaders[] = $loader;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function load(string|LocaleEnum $locale, string $domain): array
    {
        foreach ($this->loaders as $loader) {
            if ($loader->exists($locale, $domain)) {
                return $loader->load($locale, $domain);
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function exists(string|LocaleEnum $locale, string $domain): bool
    {
        return array_any($this->loaders, fn($loader) => $loader->exists($locale, $domain));
    }
}