<?php

namespace Bermuda\Polyglot;

use Bermuda\Polyglot\Cache\MessageCacheInterface;
use Bermuda\Polyglot\Detector\HttpAcceptLanguageDetector;
use Bermuda\Polyglot\Detector\LocaleDetectorChain;
use Bermuda\Polyglot\Detector\PathLocaleDetector;
use Bermuda\Polyglot\Detector\QueryLocaleDetector;
use Bermuda\Polyglot\Loader\ChainMessageLoader;

/**
 * Factory class for easy creation of the I18n instance
 */
class I18nFactory
{
    /**
     * Create an I18n instance with default configuration
     */
    public static function create(
        string $resourcesPath,
        string $defaultLocale = 'en',
        ?string $fallbackLocale = null,
        ?array $availableLocales = null,
        ?MessageCacheInterface $cache = null
    ): I18n
    {
        $fallbackLocale ??= $defaultLocale;
        $availableLocales ??= [];

        $availableLocales = array_unique(array_merge([$defaultLocale, $fallbackLocale], $availableLocales));

        // Create loaders
        $jsonLoader = new Loader\JsonFileMessageLoader($resourcesPath);
        $phpLoader = new Loader\PhpArrayMessageLoader($resourcesPath);

        $chainLoader = new ChainMessageLoader([$jsonLoader, $phpLoader]);

        // Create formatters
        $defaultFormatter = new Formatter\DefaultMessageFormatter();

        // Create plural rule provider
        $pluralRuleProvider = new PluralRule\CldrPluralRuleProvider();

        // Create translator
        $translator = new Translator(
            $defaultLocale,
            $fallbackLocale,
            $chainLoader,
            $defaultFormatter,
            $pluralRuleProvider,
            $cache
        );

        // Create locale detectors
        $httpDetector = new HttpAcceptLanguageDetector($availableLocales);
        $pathDetector = new PathLocaleDetector($availableLocales);
        $queryDetector = new QueryLocaleDetector($availableLocales);

        $localeDetector = new LocaleDetectorChain([$pathDetector, $queryDetector, $httpDetector]);

        // Create and return I18n instance
        return new I18n($translator, $localeDetector);
    }

    /**
     * Create a PSR-15 middleware for I18n
     */
    public static function createMiddleware(I18n $i18n, ?string $defaultLocale = null): I18nMiddleware
    {
        return new I18nMiddleware($i18n, $defaultLocale);
    }
}