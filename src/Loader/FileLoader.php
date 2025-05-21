<?php

namespace Bermuda\Polyglot\Loader;

use Bermuda\Polyglot\LocaleEnum;

trait FileLoader
{
    /**
     * Base path for translation files
     *
     * @var string
     */
    private string $resourcesPath;

    /**
     * @inheritDoc
     */
    public function exists(string|LocaleEnum $locale, string $domain): bool
    {
        return file_exists($this->getFilePath($locale, $domain));
    }

    /**
     * Get file path for the given locale and domain
     */
    private function getFilePath(string|LocaleEnum $locale, string $domain): string
    {
        if ($locale instanceof LocaleEnum) {
            $locale = $locale->getLanguageCode();
        }
        
        return $this->resourcesPath . $locale . DIRECTORY_SEPARATOR . $domain . '.' . $this->getExtension();
    }
    
    protected function getExtension(): string
    {
        return 'php';
    }
}
