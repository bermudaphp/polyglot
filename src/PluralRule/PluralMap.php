<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\PluralRule;

/**
 * Provides canonical examples for plural categories in different language groups
 */
final class PluralMap
{
    /** @var string[]*/
    public const array SLAVIC_LANGUAGES = ['ru', 'uk', 'be', 'pl', 'cs', 'sk', 'sr', 'hr', 'bs', 'sl', 'mk', 'bg'];

    /** @var string[]*/
    public const array WESTERN_EUROPEAN = ['en', 'de', 'nl', 'es', 'it', 'pt', 'sv', 'nb', 'no', 'da', 'el', 'hu', 'fi', 'et', 'ca', 'gl'];

    /** @var string[]*/
    public const array FRENCH_STYLE = ['fr', 'ff', 'pt_PT'];

    /** @var string[]*/
    public const array BALTIC_LANGUAGES = ['lt', 'lv'];

    /** @var string[]*/
    public const array CELTIC_LANGUAGES = ['ga', 'gd', 'cy', 'br'];

    /** @var string[]*/
    public const array SEMITIC_LANGUAGES = ['ar', 'he', 'mt'];

    /** @var string[]*/
    public const array NO_PLURAL_LANGUAGES = ['ja', 'zh', 'ko', 'vi', 'th', 'ms', 'id', 'fa', 'tr', 'az', 'hy', 'ka', 'km', 'lo'];

    /** @var string[]*/
    public const array SPECIAL_TWO_FORMS = ['ak', 'am', 'bh', 'fil', 'tl', 'guw', 'hi', 'ln', 'mg', 'nso', 'ti', 'wa'];

    /**
     * Get canonical examples for a language
     * These are representative numbers that demonstrate different plural categories
     *
     * @param string $language ISO language code (e.g., 'en', 'ru')
     * @return array<int> Array of canonical examples
     */
    public static function getCanonicalExamples(string $language): array
    {
        // Convert to lowercase
        $language = strtolower($language);

        // Extract base language from locale code (e.g., 'en_US' → 'en')
        if (str_contains($language, '_')) {
            $languageParts = explode('_', $language);
            $language = $languageParts[0];
        }

        // Проверяем принадлежность к языковым группам
        if (in_array($language, self::SLAVIC_LANGUAGES)) {
            return self::getSlavicExamples();
        }

        if (in_array($language, self::WESTERN_EUROPEAN)) {
            return self::getWesternEuropeanExamples();
        }

        if (in_array($language, self::SEMITIC_LANGUAGES)) {
            return match($language) {
                'ar' => self::getArabicExamples(),
                'he' => self::getHebrewExamples(),
                default => self::getWesternEuropeanExamples(),
            };
        }

        if (in_array($language, self::BALTIC_LANGUAGES)) {
            return match($language) {
                'lt' => self::getLithuanianExamples(),
                'lv' => self::getLatvianExamples(),
                default => self::getWesternEuropeanExamples(),
            };
        }

        if (in_array($language, self::CELTIC_LANGUAGES)) {
            return match($language) {
                'ga' => self::getIrishExamples(),
                'cy' => self::getWelshExamples(),
                default => self::getWesternEuropeanExamples(),
            };
        }

        if (in_array($language, self::FRENCH_STYLE)) {
            return self::getFrenchStyleExamples();
        }

        if (in_array($language, self::NO_PLURAL_LANGUAGES)) {
            return self::getNoPluralExamples();
        }

        if (in_array($language, self::SPECIAL_TWO_FORMS)) {
            return self::getSpecialTwoFormsExamples();
        }

        // Special cases for specific languages
        return match($language) {
            'ro' => self::getRomanianExamples(),
            default => self::getWesternEuropeanExamples(), // Default fallback
        };
    }

    /**
     * Examples for Slavic languages (Russian, Polish, Czech, etc.)
     * Categories: one, few, many
     */
    private static function getSlavicExamples(): array
    {
        return [
            1,   // one (1, 21, 31...) - единственное число
            2,   // few (2-4, 22-24...) - малое количество
            5,   // many (0, 5-20, 25-30...) - большое количество
            0,   // many (для русского), zero (для некоторых других)
            11,  // many (в большинстве славянских)
        ];
    }

    /**
     * Examples for Western European languages (English, German, Spanish, etc.)
     * Categories: one, other
     */
    private static function getWesternEuropeanExamples(): array
    {
        return [
            1,   // one
            0,   // other
            2,   // other
            5,   // other
            100, // other
        ];
    }

    /**
     * Examples for French and similar languages
     * Categories: one (0, 1), other (2+)
     */
    private static function getFrenchStyleExamples(): array
    {
        return [
            0,   // one
            1,   // one
            2,   // other
            100, // other
        ];
    }

    /**
     * Examples for Arabic
     * Categories: zero, one, two, few, many, other
     */
    private static function getArabicExamples(): array
    {
        return [
            0,    // zero
            1,    // one
            2,    // two
            3,    // few (3-10)
            11,   // many (11-99)
            100,  // other (100+)
            101,  // other
        ];
    }

    /**
     * Examples for Hebrew
     * Categories: one, two, many, other
     */
    private static function getHebrewExamples(): array
    {
        return [
            1,    // one
            2,    // two
            10,   // many
            20,   // other
        ];
    }

    /**
     * Examples for Lithuanian
     * Categories: one, few, many, other
     */
    private static function getLithuanianExamples(): array
    {
        return [
            1,    // one
            2,    // few (ends in 2-9, except 12-19)
            10,   // other (ends in 0 or 11-19)
            11,   // other
        ];
    }

    /**
     * Examples for Latvian
     * Categories: zero, one, other
     */
    private static function getLatvianExamples(): array
    {
        return [
            0,    // zero
            1,    // one (ends in 1, except 11)
            11,   // other (ends in 0 or 11-19)
            21,   // one (ends in 1, except 11)
            2,    // other
        ];
    }

    /**
     * Examples for Irish
     * Categories: one, two, few, many, other
     */
    private static function getIrishExamples(): array
    {
        return [
            1,    // one
            2,    // two
            3,    // few (3-6)
            7,    // many (7-10)
            11,   // other (11+)
        ];
    }

    /**
     * Examples for Welsh
     * Categories: zero, one, two, few, many, other
     */
    private static function getWelshExamples(): array
    {
        return [
            0,    // zero
            1,    // one
            2,    // two
            3,    // few (3)
            6,    // many (6)
            4,    // other (4, 5, 7-9...)
        ];
    }

    /**
     * Examples for Romanian
     * Categories: one, few, other
     */
    private static function getRomanianExamples(): array
    {
        return [
            1,    // one
            2,    // few (0, 2-19)
            0,    // few
            20,   // other (20+)
        ];
    }

    /**
     * Examples for languages without plural forms
     * Category: other (all numbers)
     */
    private static function getNoPluralExamples(): array
    {
        return [
            0,    // other
            1,    // other
            2,    // other
            5,    // other
            10,   // other
        ];
    }

    /**
     * Examples for languages with special two forms rules
     * (Hindi, Filipino, Tagalog, etc.)
     * Categories: one (0, 1), other (2+)
     */
    private static function getSpecialTwoFormsExamples(): array
    {
        return [
            0,    // one
            1,    // one
            2,    // other
            5,    // other
            10,   // other
        ];
    }
}