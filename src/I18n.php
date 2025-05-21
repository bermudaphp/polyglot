<?php

namespace Bermuda\Polyglot;

use Bermuda\Polyglot\Detector\LocaleDetectorChain;
use Bermuda\Polyglot\Detector\LocaleDetectorInterface;
use Bermuda\Polyglot\Detector\PsrRequestAwareLocaleDetectorInterface;
use Bermuda\Polyglot\Exception\InvalidLocaleException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Main facade for the library
 */
class I18n
{
    private(set) TranslatorInterface $translator;
    private(set) LocaleDetectorInterface $localeDetector;

    /**
     * @param TranslatorInterface $translator
     * @param LocaleDetectorInterface|null $localeDetector
     */
    public function __construct(
        TranslatorInterface $translator,
        ?LocaleDetectorInterface $localeDetector = null
    )
    {
        $this->translator = $translator;
        $this->localeDetector = $localeDetector ?? new LocaleDetectorChain();
    }

    /**
     * Translate a key
     */
    public function translate(string $key, array $parameters = [], ?string $domain = null): string
    {
        return $this->translator->translate($key, $parameters, $domain);
    }

    /**
     * Shorthand for translate
     */
    public function t(string $key, array $parameters = [], ?string $domain = null): string
    {
        return $this->translate($key, $parameters, $domain);
    }

    /**
     * Translate with plural support
     */
    public function translatePlural(string $key, int $count, array $parameters = [], ?string $domain = null): string
    {
        return $this->translator->translatePlural($key, $count, $parameters, $domain);
    }

    /**
     * Shorthand for translatePlural
     */
    public function tp(string $key, int $count, array $parameters = [], ?string $domain = null): string
    {
        return $this->translatePlural($key, $count, $parameters, $domain);
    }

    /**
     * Set the current locale
     * @throws InvalidLocaleException
     */
    public function setLocale(string|Locale|LocaleEnum $locale): self
    {
        $this->translator->locale = $locale;
        return $this;
    }

    public function getLocale(): Locale
    {
        return $this->translator->locale;
    }

    /**
     * Detect and set the locale based on configured detectors
     * @throws InvalidLocaleException
     */
    public function detectAndSetLocale(?string $defaultLocale = null): self
    {
        $locale = $this->localeDetector->detect();

        if ($locale !== null) {
            $this->setLocale($locale);
        } elseif ($defaultLocale !== null) {
            $this->setLocale($defaultLocale);
        }

        return $this;
    }

    /**
     * Detect and set the locale based on PSR-7 request
     * @throws InvalidLocaleException
     */
    public function detectAndSetLocaleFromRequest(ServerRequestInterface $request, ?string $defaultLocale = null): self
    {
        $locale = null;

        if ($this->localeDetector instanceof PsrRequestAwareLocaleDetectorInterface) {
            $locale = $this->localeDetector->detectFromRequest($request);
        } else {
            $locale = $this->localeDetector->detect();
        }

        if ($locale !== null) {
            $this->setLocale($locale);
        } elseif ($defaultLocale !== null) {
            $this->setLocale($defaultLocale);
        }

        return $this;
    }

    /**
     * Get the translator instance
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Get the locale detector instance
     */
    public function getLocaleDetector(): Detector\LocaleDetectorInterface
    {
        return $this->localeDetector;
    }
}
