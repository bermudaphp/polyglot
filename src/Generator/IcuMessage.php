<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Generator;

use Bermuda\Polyglot\LocaleEnum;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;

/**
 * Facade for ICU message template generation
 */
final class IcuMessage
{
    /**
     * Create a new message builder for specific locale
     *
     * @param string|LocaleEnum $locale Locale code
     * @param PluralRuleProviderInterface|null $pluralRuleProvider Optional custom rule provider
     * @return IcuMessageBuilder Message builder
     */
    public static function for(string|LocaleEnum $locale, ?PluralRuleProviderInterface $pluralRuleProvider = null): IcuMessageBuilder
    {
        return new IcuMessageBuilder($locale, $pluralRuleProvider);
    }

    /**
     * Create a new plural builder
     *
     * @param string $variable Variable name
     * @param string|LocaleEnum $locale Locale code
     * @param PluralRuleProviderInterface|null $pluralRuleProvider Optional custom rule provider
     * @return PluralBuilder Plural builder
     */
    public static function plural(string $variable, string|LocaleEnum $locale, ?PluralRuleProviderInterface $pluralRuleProvider = null): PluralBuilder
    {
        return new IcuMessageBuilder($locale, $pluralRuleProvider)->plural($variable);
    }

    /**
     * Create a new select builder
     *
     * @param string $variable Variable name
     * @return SelectBuilder Select builder
     */
    public static function select(string $variable): SelectBuilder
    {
        return new SelectBuilder($variable);
    }

    /**
     * Create a gender template with a simple API
     *
     * @param string $variable Gender variable name
     * @param string $maleText Text for males
     * @param string $femaleText Text for females
     * @param string $otherText Text for others
     * @return string Icu message template
     */
    public static function gender(string $variable, string $maleText, string $femaleText, string $otherText): string
    {
        return self::select($variable)
            ->when('male', $maleText)
            ->when('female', $femaleText)
            ->otherwise($otherText)
            ->build();
    }

    /**
     * Create a date formatter with a simple API
     *
     * @param string $variable Date variable
     * @param string $style Date style (short, medium, long, full)
     * @return string Icu date format
     */
    public static function date(string $variable, string $style = 'medium'): string
    {
        return '{' . $variable . ', date, ' . $style . '}';
    }

    /**
     * Create a time formatter with a simple API
     *
     * @param string $variable Time variable
     * @param string $style Time style (short, medium, long, full)
     * @return string Icu date format
     */
    public static function time(string $variable, string $style = 'medium'): string
    {
        return '{' . $variable . ', time, ' . $style . '}';
    }

    /**
     * Create a number formatter with a simple API
     *
     * @param string $variable Number variable
     * @param string|null $style Number style (currency, percent, etc.)
     * @param string|null $options Additional options
     * @return string Icu number format
     */
    public static function number(string $variable, ?string $style = null, ?string $options = null): string
    {
        $result = '{' . $variable . ', number';

        if ($style !== null) {
            $result .= ', ' . $style;

            if ($options !== null) {
                $result .= ', ' . $options;
            }
        }

        return $result . '}';
    }
}

