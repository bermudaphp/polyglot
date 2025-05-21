<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\PluralRule;


/**
 * A class representing a plural rule for a specific locale
 */
readonly class PluralRule
{
    /**
     * @param string $locale Locale code
     * @param int $numberOfPlurals Number of plural forms in this locale
     * @param callable(int): PluralCategory $ruleFn Function that returns the correct plural category for a count
     */
    public function __construct(
        public string $locale,
        public int $numberOfPlurals,
        private \Closure $ruleFn
    ) {
    }

    /**
     * Get the correct plural category for a count
     *
     * @param int $count The count to get plural category for
     * @return PluralCategory The plural category
     */
    public function getCategory(int $count): PluralCategory
    {
        return ($this->ruleFn)($count);
    }

    /**
     * Creates a plural rule for English language (en)
     *
     * Two forms: one (n=1), other (everything else)
     */
    public static function english(): self
    {
        return new self('en', 2, function (int $n): PluralCategory {
            return $n === 1 ? PluralCategory::ONE : PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Chinese language (zh)
     *
     * One form: other (all numbers)
     */
    public static function chinese(): self
    {
        return new self('zh', 1, function (): PluralCategory {
            return PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Spanish language (es)
     *
     * Two forms: one (n=1), other (everything else)
     */
    public static function spanish(): self
    {
        return new self('es', 2, function (int $n): PluralCategory {
            return $n === 1 ? PluralCategory::ONE : PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Arabic language (ar)
     *
     * Six forms: zero, one, two, few, many, other
     */
    public static function arabic(): self
    {
        return new self('ar', 6, function (int $n): PluralCategory {
            if ($n === 0) {
                return PluralCategory::ZERO;
            }
            if ($n === 1) {
                return PluralCategory::ONE;
            }
            if ($n === 2) {
                return PluralCategory::TWO;
            }
            if ($n % 100 >= 3 && $n % 100 <= 10) {
                return PluralCategory::FEW;
            }
            if ($n % 100 >= 11 && $n % 100 <= 99) {
                return PluralCategory::MANY;
            }
            return PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Portuguese language (pt)
     *
     * Two forms: one (n=1), other (everything else)
     */
    public static function portuguese(): self
    {
        return new self('pt', 2, function (int $n): PluralCategory {
            return $n === 1 ? PluralCategory::ONE : PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Indonesian/Malay language (id/ms)
     *
     * One form: other (all numbers)
     */
    public static function indonesian(): self
    {
        return new self('id', 1, function (): PluralCategory {
            return PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for French language (fr)
     *
     * Two forms: one (n=0,1), other (n>1)
     */
    public static function french(): self
    {
        return new self('fr', 2, function (int $n): PluralCategory {
            return ($n === 0 || $n === 1) ? PluralCategory::ONE : PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Japanese language (ja)
     *
     * One form: other (all numbers)
     */
    public static function japanese(): self
    {
        return new self('ja', 1, function (): PluralCategory {
            return PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Russian language (ru)
     *
     * Three forms: one, few, many
     */
    public static function russian(): self
    {
        return new self('ru', 3, function (int $n): PluralCategory {
            $mod10 = $n % 10;
            $mod100 = $n % 100;

            if ($mod10 === 1 && $mod100 !== 11) {
                return PluralCategory::ONE;
            }

            if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
                return PluralCategory::FEW;
            }

            return PluralCategory::MANY;
        });
    }

    /**
     * Creates a plural rule for German language (de)
     *
     * Two forms: one (n=1), other (everything else)
     */
    public static function german(): self
    {
        return new self('de', 2, function (int $n): PluralCategory {
            return $n === 1 ? PluralCategory::ONE : PluralCategory::OTHER;
        });
    }

    /**
     * Creates a plural rule for Polish language (pl)
     *
     * Three forms: one, few, many
     */
    public static function polish(): self
    {
        return new self('pl', 3, function (int $n): PluralCategory {
            if ($n === 1) {
                return PluralCategory::ONE;
            }

            $mod10 = $n % 10;
            $mod100 = $n % 100;

            if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
                return PluralCategory::FEW;
            }

            return PluralCategory::MANY;
        });
    }

    /**
     * Creates a plural rule for a specific locale.
     * This is a factory method that returns the appropriate rule
     * for well-known languages, or a default rule for unknown ones.
     *
     * @param string $locale The locale code
     * @return PluralRule|null The plural rule for the locale
     */
    public static function forLocale(string $locale):? self
    {
        // Extract language code from locale (e.g., 'en_US' -> 'en')
        $language = strtolower(substr($locale, 0, 2));

        return match($language) {
            'en' => self::english(),
            'zh' => self::chinese(),
            'es' => self::spanish(),
            'ar' => self::arabic(),
            'pt' => self::portuguese(),
            'id', 'ms' => self::indonesian(),
            'fr' => self::french(),
            'ja' => self::japanese(),
            'ru' => self::russian(),
            'de' => self::german(),
            'pl' => self::polish(),
            default => null,
        };
    }
}