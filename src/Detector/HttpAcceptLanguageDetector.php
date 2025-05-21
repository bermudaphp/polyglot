<?php

namespace Bermuda\Polyglot\Detector;

use Bermuda\Polyglot\Detector;
use Bermuda\Polyglot\Locale;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP Accept-Language header locale detector
 */
class HttpAcceptLanguageDetector implements Detector\PsrRequestAwareLocaleDetectorInterface
{
    /** @var string[] */
    private array $availableLocales;

    /**
     * @param string[] $availableLocales List of available locales
     */
    public function __construct(array $availableLocales)
    {
        $this->availableLocales = $availableLocales;
    }

    /**
     * Detect locale from Accept-Language header using global $_SERVER
     */
    public function detect(): ?string
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        return $this->detectFromAcceptLanguage($acceptLanguage);
    }

    /**
     * Detect locale from PSR-7 request
     */
    public function detectFromRequest(ServerRequestInterface $request): ?string
    {
        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        if (empty($acceptLanguage)) {
            return null;
        }

        return $this->detectFromAcceptLanguage($acceptLanguage);
    }

    /**
     * Detect locale from an Accept-Language header value
     */
    private function detectFromAcceptLanguage(string $acceptLanguage): ?string
    {
        $acceptedLocales = $this->parseAcceptLanguage($acceptLanguage);

        // Find the best match
        foreach ($acceptedLocales as $locale => $quality) {
            // Normalize locale to handle both hyphen and underscore formats
            $normalizedLocale = Locale::normalize($locale);

            // Check for an exact match (accounting for hyphen/underscore variants)
            foreach ($this->availableLocales as $availableLocale) {
                if (Locale::normalize($availableLocale) === $normalizedLocale) {
                    return $availableLocale;
                }
            }

            // Get just the language part (e.g., 'en' from 'en-US')
            $language = strtolower(substr($locale, 0, 2));

            // Check for a language match if exact match failed
            foreach ($this->availableLocales as $availableLocale) {
                if (strtolower(substr($availableLocale, 0, 2)) === $language) {
                    return $availableLocale;
                }
            }
        }

        return null;
    }

    /**
     * Parse the Accept-Language header into an array of [locale => quality]
     *
     * @return array<string, float>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $result = [];

        // Example: en-US,en;q=0.9,fr;q=0.8
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $part = trim($part);
            $qualityParts = explode(';q=', $part);

            $locale = $qualityParts[0];
            $quality = $qualityParts[1] ?? 1.0;

            $result[$locale] = (float)$quality;
        }

        // Sort by quality (highest first)
        arsort($result);

        return $result;
    }

    /**
     * Normalize locale to handle both hyphen and underscore formats
     */
    private function normalizeLocale(string $locale): string
    {
        // Convert to lowercase and replace hyphens with underscores for consistent comparison
        return str_replace('-', '_', strtolower($locale));
    }
}