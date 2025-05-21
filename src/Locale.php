<?php

namespace Bermuda\Polyglot;

use Bermuda\Polyglot\Exception\InvalidLocaleException;

/**
 * Immutable locale value object with validation
 */
readonly class Locale implements \Stringable
{
    public string $language;
    public ?string $region;
    public ?string $variant;

    public const string regex = '/^([a-z]{2})(?:[-_]([A-Z]{2}))?(?:[-_]([a-zA-Z0-9]+))?$/';

    /**
     * @param string|LocaleEnum $locale
     * @throws InvalidLocaleException
     */
    public function __construct(string|LocaleEnum $locale)
    {
        if (is_string($locale)) {
            if (preg_match(self::regex, $locale, $matches) !== 1){
                throw new InvalidLocaleException("Invalid locale format: $locale");
            }

            $this->language = $matches[1];
            $this->region = $matches[2] ?? null;
            $this->variant = $matches[3] ?? null;
        } else {
            $this->language = $locale->getLanguageCode();
            $this->region = $locale->getRegionCode();
            $this->variant = null;
        }
    }

    public static function isValidLocale(string $locale): bool
    {
        return preg_match(self::regex, $locale) === 1;
    }

    public function __toString(): string
    {
        $result = $this->language;
        if ($this->region !== null) {
            $result .= '_' . $this->region;
        }
        if ($this->variant !== null) {
            $result .= '_' . $this->variant;
        }
        return $result;
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public static function normalize(string $locale): string
    {
        return str_replace('-', '_', strtolower($locale));
    }

    /**
     * Get all possible fallbacks for this locale
     *
     * @return string[]
     */
    public function getFallbacks(): array
    {
        $fallbacks = [];

        if ($this->variant !== null && $this->region !== null) {
            $fallbacks[] = $this->language . '_' . $this->region;
        }

        if ($this->region !== null) {
            $fallbacks[] = $this->language;
        }

        return $fallbacks;
    }
}