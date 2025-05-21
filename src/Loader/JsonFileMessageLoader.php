<?php

namespace Bermuda\Polyglot\Loader;


use Bermuda\Polyglot\LocaleEnum;

/**
 * JSON file message loader
 */
class JsonFileMessageLoader implements MessageLoaderInterface
{
    use FileLoader;

    /** @var string */
    private string $resourcesPath;

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

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read translation file: $filePath");
        }

        $messages = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to parse translation file: $filePath - " . json_last_error_msg());
        }

        return $messages ?? [];
    }
}