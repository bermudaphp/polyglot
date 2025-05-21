<?php

namespace Bermuda\Polyglot\Generator;

use Bermuda\Polyglot\LocaleEnum;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;

/**
 * Main builder for Icu messages
 */
class IcuMessageBuilder
{
    private string $locale;
    private PluralRuleProviderInterface $pluralRuleProvider;

    public function __construct(string|LocaleEnum $locale, ?PluralRuleProviderInterface $pluralRuleProvider = null)
    {
        $this->locale = is_string($locale) ? $locale : $locale->getLanguageCode();
        $this->pluralRuleProvider = $pluralRuleProvider ?? new CldrPluralRuleProvider();
    }

    /**
     * Start building a plural message
     *
     * @param string $variable Variable name
     * @return PluralBuilder Plural builder
     */
    public function plural(string $variable): PluralBuilder
    {
        return new PluralBuilder($variable, $this->locale, $this->pluralRuleProvider);
    }

    /**
     * Start building a select message
     *
     * @param string $variable Variable name
     * @return SelectBuilder Select builder
     */
    public function select(string $variable): SelectBuilder
    {
        return new SelectBuilder($variable);
    }

    /**
     * Create a message with a placeholder variable
     *
     * @param string $text Text with {variable} placeholders
     * @return string Icu message format
     */
    public function message(string $text): string
    {
        return $text;
    }
}