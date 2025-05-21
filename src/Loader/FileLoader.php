<?php

namespace Bermuda\Polyglot\Loader;

use Bermuda\Polyglot\LocaleEnum;

trait FileLoader
{
    /**
     * @inheritDoc
     */
    public function exists(string|LocaleEnum $locale, string $domain): bool
    {
        return file_exists($this->getFilePath($locale, $domain));
    }

    private function getFilePath(string|LocaleEnum $locale, string $domain): string
    {
        if ($locale instanceof LocaleEnum) $locale = $locale->getLanguageCode();
        return $this->resourcesPath . $locale . DIRECTORY_SEPARATOR . $domain . '.php';
    }
}