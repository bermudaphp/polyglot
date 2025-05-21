<?php

namespace Bermuda\Polyglot\Loader;

use Bermuda\Polyglot\LocaleEnum;

/**
 * PHP Array file message loader
 */
class PhpArrayMessageLoader implements MessageLoaderInterface
{
    use FileLoader;

    /**
     * @param string $resourcesPath Base path for translation files
     */
    public function __construct(string $resourcesPath)
    {
        $this->resourcesPath = rtrim($resourcesPath, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritDoc
     */
    public function load(string|LocaleEnum $locale, string $domain): array
    {
        $filePath = $this->getFilePath($locale, $domain);

        if (!file_exists($filePath)) {
            return [];
        }

        $messages = include $filePath;

        if (!is_array($messages)) {
            throw new \RuntimeException("Translation file does not return an array: $filePath");
        }

        return $messages;
    }
}
