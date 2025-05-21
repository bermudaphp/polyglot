<?php

namespace Bermuda\Polyglot;

use Bermuda\Polyglot\Exception\I18nException;

/**
 * Basic interface for translations that provides methods to translate messages
 * with support for parameters, domains, and plural forms.
 */
interface TranslatorInterface
{
    public Locale $locale {set; get;}

    /**
     * Translate a key into the current locale
     *
     * @param string $key The translation key (message identifier)
     * @param array $parameters Parameters to substitute in the message
     * @param string|null $domain Translation domain (optional)
     * @param LocaleEnum|string|null $locale Target locale (if null, current locale is used)
     *
     * @return string Translated message with substituted parameters
     * @throws I18nException If translation is not found or other error occurs
     */
    public function translate(string $key, array $parameters = [], ?string $domain = null, null|string|LocaleEnum $locale = null): string;

    /**
     * Translate with plural support
     *
     * @param string $key The translation key (message identifier)
     * @param int $count The quantity that determines which plural form to use
     * @param array $parameters Additional parameters to substitute in the message
     * @param string|null $domain Translation domain (optional)
     * @param LocaleEnum|string|null $locale Target locale (if null, current locale is used)
     *
     * @return string Translated message with the correct plural form
     * @throws I18nException If translation is not found or other error occurs
     */
    public function translatePlural(string $key, int $count, array $parameters = [], ?string $domain = null, null|string|LocaleEnum $locale = null): string;
}