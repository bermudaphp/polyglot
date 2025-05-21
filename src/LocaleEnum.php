<?php

declare(strict_types=1);

namespace Bermuda\Polyglot;

use Bermuda\Polyglot\Exception\InvalidLocaleException;

/**
 * Enum for common locale codes
 */
enum LocaleEnum: string
{
    // English
    case ENGLISH = 'en';
    case ENGLISH_US = 'en_US';
    case ENGLISH_GB = 'en_GB';
    case ENGLISH_CA = 'en_CA';
    case ENGLISH_AU = 'en_AU';
    case ENGLISH_NZ = 'en_NZ';
    case ENGLISH_IE = 'en_IE';
    case ENGLISH_IN = 'en_IN';
    case ENGLISH_ZA = 'en_ZA';

    // Spanish
    case SPANISH = 'es';
    case SPANISH_ES = 'es_ES';
    case SPANISH_MX = 'es_MX';
    case SPANISH_AR = 'es_AR';
    case SPANISH_CO = 'es_CO';
    case SPANISH_CL = 'es_CL';

    // French
    case FRENCH = 'fr';
    case FRENCH_FR = 'fr_FR';
    case FRENCH_CA = 'fr_CA';
    case FRENCH_BE = 'fr_BE';
    case FRENCH_CH = 'fr_CH';

    // German
    case GERMAN = 'de';
    case GERMAN_DE = 'de_DE';
    case GERMAN_AT = 'de_AT';
    case GERMAN_CH = 'de_CH';

    // Russian and other Slavic
    case RUSSIAN = 'ru';
    case RUSSIAN_RU = 'ru_RU';
    case UKRAINIAN = 'uk';
    case UKRAINIAN_UA = 'uk_UA';
    case BELARUSIAN = 'be';
    case POLISH = 'pl';
    case POLISH_PL = 'pl_PL';
    case CZECH = 'cs';
    case CZECH_CZ = 'cs_CZ';
    case SLOVAK = 'sk';
    case SLOVAK_SK = 'sk_SK';
    case BULGARIAN = 'bg';
    case BULGARIAN_BG = 'bg_BG';
    case SERBIAN = 'sr';
    case CROATIAN = 'hr';
    case SLOVENIAN = 'sl';

    // Portuguese
    case PORTUGUESE = 'pt';
    case PORTUGUESE_PT = 'pt_PT';
    case PORTUGUESE_BR = 'pt_BR';

    // Italian
    case ITALIAN = 'it';
    case ITALIAN_IT = 'it_IT';
    case ITALIAN_CH = 'it_CH';

    // Dutch
    case DUTCH = 'nl';
    case DUTCH_NL = 'nl_NL';
    case DUTCH_BE = 'nl_BE';

    // Scandinavian
    case DANISH = 'da';
    case DANISH_DK = 'da_DK';
    case NORWEGIAN = 'no';
    case NORWEGIAN_NO = 'no_NO';
    case SWEDISH = 'sv';
    case SWEDISH_SE = 'sv_SE';
    case FINNISH = 'fi';
    case FINNISH_FI = 'fi_FI';
    case ICELANDIC = 'is';
    case ICELANDIC_IS = 'is_IS';

    // Asian
    case CHINESE_SIMPLIFIED = 'zh_CN';
    case CHINESE_TRADITIONAL = 'zh_TW';
    case CHINESE_HK = 'zh_HK';
    case JAPANESE = 'ja';
    case JAPANESE_JP = 'ja_JP';
    case KOREAN = 'ko';
    case KOREAN_KR = 'ko_KR';
    case VIETNAMESE = 'vi';
    case VIETNAMESE_VN = 'vi_VN';
    case THAI = 'th';
    case THAI_TH = 'th_TH';
    case INDONESIAN = 'id';
    case INDONESIAN_ID = 'id_ID';
    case MALAY = 'ms';
    case MALAY_MY = 'ms_MY';
    case HINDI = 'hi';
    case HINDI_IN = 'hi_IN';
    case BENGALI = 'bn';
    case BENGALI_BD = 'bn_BD';
    case BENGALI_IN = 'bn_IN';
    case URDU = 'ur';
    case URDU_PK = 'ur_PK';
    case ARABIC = 'ar';
    case ARABIC_SA = 'ar_SA';
    case ARABIC_EG = 'ar_EG';
    case PERSIAN = 'fa';
    case PERSIAN_IR = 'fa_IR';
    case HEBREW = 'he';
    case HEBREW_IL = 'he_IL';
    case TURKISH = 'tr';
    case TURKISH_TR = 'tr_TR';

    // Other European
    case GREEK = 'el';
    case GREEK_GR = 'el_GR';
    case HUNGARIAN = 'hu';
    case HUNGARIAN_HU = 'hu_HU';
    case ROMANIAN = 'ro';
    case ROMANIAN_RO = 'ro_RO';
    case LATVIAN = 'lv';
    case LATVIAN_LV = 'lv_LV';
    case LITHUANIAN = 'lt';
    case LITHUANIAN_LT = 'lt_LT';
    case ESTONIAN = 'et';
    case ESTONIAN_EE = 'et_EE';
    case ALBANIAN = 'sq';
    case ALBANIAN_AL = 'sq_AL';

    // Convert locale string to Locale object

    /**
     * @throws InvalidLocaleException
     */
    public function toLocale(): Locale
    {
        return new Locale($this);
    }

    // Get standard language code (first 2 characters)
    public function getLanguageCode(): string
    {
        return substr($this->value, 0, 2);
    }

    // Get region code if available
    public function getRegionCode(): ?string
    {
        if (str_contains($this->value, '_')) {
            $parts = explode('_', $this->value);
            return $parts[1] ?? null;
        }

        return null;
    }

    /**
     * Create from string with format normalization (returns null if not a valid enum value)
     * Handles both "en-US" and "en_US" formats
     */
    public static function fromString(string $locale): ?self
    {
        // First try direct match using PHP's native tryFrom
        $direct = self::tryFrom($locale);
        if ($direct !== null) {
            return $direct;
        }

        // Normalize locale string for comparison
        $normalizedLocale = strtolower(str_replace('-', '_', $locale));

        // Try again with the normalized string
        $normalized = self::tryFrom($normalizedLocale);
        if ($normalized !== null) {
            return $normalized;
        }

        // Manually search through all cases
        return array_find(self::cases(), static fn($case) => strtolower($case->value) === $normalizedLocale);

    }

    // Get default locale for a language
    public static function getDefaultForLanguage(string $language): ?self
    {
        $language = strtolower($language);

        // Map of language codes to their default locale
        return match ($language) {
            'en' => self::ENGLISH,
            'es' => self::SPANISH,
            'fr' => self::FRENCH,
            'de' => self::GERMAN,
            'ru' => self::RUSSIAN,
            'zh' => self::CHINESE_SIMPLIFIED,
            'ja' => self::JAPANESE,
            'ko' => self::KOREAN,
            'ar' => self::ARABIC,
            'pt' => self::PORTUGUESE,
            'it' => self::ITALIAN,
            'nl' => self::DUTCH,
            'pl' => self::POLISH,
            'tr' => self::TURKISH,
            'vi' => self::VIETNAMESE,
            'th' => self::THAI,
            'cs' => self::CZECH,
            'uk' => self::UKRAINIAN,
            'ro' => self::ROMANIAN,
            'sv' => self::SWEDISH,
            'hu' => self::HUNGARIAN,
            'el' => self::GREEK,
            'da' => self::DANISH,
            'fi' => self::FINNISH,
            'sr' => self::SERBIAN,
            'lt' => self::LITHUANIAN,
            'sk' => self::SLOVAK,
            'bg' => self::BULGARIAN,
            'hr' => self::CROATIAN,
            'sl' => self::SLOVENIAN,
            'et' => self::ESTONIAN,
            'lv' => self::LATVIAN,
            'id' => self::INDONESIAN,
            'ms' => self::MALAY,
            'hi' => self::HINDI,
            'bn' => self::BENGALI,
            'fa' => self::PERSIAN,
            'he' => self::HEBREW,
            'ur' => self::URDU,
            'no' => self::NORWEGIAN,
            'sq' => self::ALBANIAN,
            'is' => self::ICELANDIC,
            default => null,
        };
    }

    // Get all locales for a specific language
    public static function getAllForLanguage(string $language): array
    {
        $language = strtolower($language);
        $matchingLocales = [];

        foreach (self::cases() as $case) {
            if (substr($case->value, 0, 2) === $language) {
                $matchingLocales[] = $case;
            }
        }

        return $matchingLocales;
    }

    // Get all available locales as string array
    public static function getAvailableLocales(): array
    {
        return array_map(fn(self $locale) => $locale->value, self::cases());
    }
}