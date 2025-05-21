<?php

namespace Bermuda\Polyglot;

use Bermuda\Polyglot\Cache\MessageCacheInterface;
use Bermuda\Polyglot\Exception\I18nException;
use Bermuda\Polyglot\Exception\InvalidLocaleException;
use Bermuda\Polyglot\Exception\RuleNotFoundException;
use Bermuda\Polyglot\Exception\TranslationNotFoundException;
use Bermuda\Polyglot\PluralRule\PluralCategory;
use Bermuda\Polyglot\Formatter\IcuMessageFormatter;

/**
 * Main translator class that implements translation functionality.
 * Handles message translations with parameter substitution, plural forms,
 * and fallback mechanisms for missing translations.
 */
class Translator implements TranslatorInterface
{
    /**
     * Current locale for translations.
     * Automatically converts between locale formats and Locale objects.
     */
    public Locale $locale {
        get {
            return $this->locale;
        }
        set(string|LocaleEnum|Locale $value) {
            $this->locale = $value instanceof Locale ? $value : new Locale($value);
        }
    }

    /**
     * Fallback locale used when a translation is not found in the primary locale.
     * Set to null to disable fallback functionality.
     */
    public ?Locale $fallbackLocale {
        get {
            return $this->fallbackLocale;
        }
        set(null|string|LocaleEnum|Locale $value) {
            if ($value == null) {
                $this->fallbackLocale = null;
                return;
            }

            $this->fallbackLocale = $value instanceof Locale ? $value : new Locale($value);
        }
    }

    /**
     * Cache of loaded messages organized by locale and domain.
     * Structure: [locale => [domain => [key => translation]]]
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private array $loadedMessages = [];

    /**
     * Creates a new translator with the specified configuration.
     *
     * @param string|LocaleEnum|Locale $locale Primary locale for translations
     * @param null|string|LocaleEnum|Locale $fallbackLocale Fallback locale when translations are missing
     * @param Loader\MessageLoaderInterface $loader Loader used to fetch translations
     * @param Formatter\MessageFormatterInterface $formatter Formatter for parameter substitution
     * @param PluralRule\PluralRuleProviderInterface $pluralRuleProvider Provider for plural rules
     * @param MessageCacheInterface|null $cache Optional cache for translations
     *
     * @throws InvalidLocaleException If provided locales are invalid
     */
    public function __construct(
        string|LocaleEnum|Locale $locale,
        null|string|LocaleEnum|Locale $fallbackLocale,
        private readonly Loader\MessageLoaderInterface $loader,
        private readonly Formatter\MessageFormatterInterface $formatter,
        private readonly PluralRule\PluralRuleProviderInterface $pluralRuleProvider,
        private readonly ?MessageCacheInterface $cache = null,
    )
    {
        $this->locale = $locale;
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * Translates a message by key into the current or specified locale.
     * Supports parameter substitution and fallback locales.
     *
     * @param string $key The translation key (message identifier)
     * @param array $parameters Parameters to substitute in the message
     * @param string|null $domain Translation domain (optional)
     * @param LocaleEnum|string|null $locale Target locale (if null, current locale is used)
     *
     * @return string Translated message with substituted parameters
     * @throws I18nException If a critical error occurs during translation
     */
    public function translate(string $key, array $parameters = [], ?string $domain = null, null|string|LocaleEnum $locale = null): string
    {
        // Normalize locale if it's provided as an enum
        if ($locale instanceof LocaleEnum) {
            $locale = $locale->getLanguageCode();
        }

        // Set defaults if not provided
        $domain ??= 'messages';
        $locale ??= (string) $this->locale;

        // Prevent infinite recursion by tracking processed locales
        static $processedLocales = [];
        $localeKey = $locale . '|' . $domain . '|' . $key;

        if (isset($processedLocales[$localeKey])) {
            // Reset static variable for future calls and return fallback
            $processedLocales = [];
            return str_contains($key, '.') ? substr($key, strrpos($key, '.') + 1) : $key;
        }

        $processedLocales[$localeKey] = true;

        try {
            // Attempt to find the translation
            $message = $this->findTranslation($key, $locale, $domain);

            // Handle array responses (which should be processed by translatePlural)
            if (is_array($message)) {
                if (isset($message[PluralCategory::OTHER->value])) {
                    $message = $message[PluralCategory::OTHER->value];
                } elseif (!empty($message)) {
                    // Use the first item if OTHER is not available
                    $message = reset($message);
                } else {
                    throw new TranslationNotFoundException($key, $locale, $domain);
                }
            }

            // Add locale to parameters for proper formatting
            $formatterParams = $parameters;
            if (property_exists(IcuMessageFormatter::class, 'localeKey')) {
                $formatterParams[IcuMessageFormatter::localeKey] = $locale;
            }

            $result = $this->formatter->format($message, $formatterParams);

            // Reset static variable before returning
            $processedLocales = [];
            return $result;

        } catch (TranslationNotFoundException) {
            // Try with fallback locale if available and different from current
            if ($this->fallbackLocale !== null && (string)$this->fallbackLocale !== $locale) {
                $processedLocales = []; // Reset before recursion
                return $this->translate($key, $parameters, $domain, (string)$this->fallbackLocale);
            }

            // Reset the processed locales tracker
            $processedLocales = [];

            // Return last segment of the key as fallback
            return str_contains($key, '.') ? substr($key, strrpos($key, '.') + 1) : $key;
        }
    }

    /**
     * Translates a message with plural forms based on the given count.
     * Selects the appropriate plural form using the plural rules for the locale.
     *
     * @param string $key The translation key (message identifier)
     * @param int $count The quantity that determines which plural form to use
     * @param array $parameters Additional parameters to substitute in the message
     * @param string|null $domain Translation domain (optional)
     * @param LocaleEnum|string|null $locale Target locale (if null, current locale is used)
     *
     * @return string Translated message with the correct plural form
     * @throws I18nException If a critical error occurs during translation
     * @throws RuleNotFoundException If no plural rule is found for the locale
     */
    public function translatePlural(string $key, int $count, array $parameters = [], ?string $domain = null, null|string|LocaleEnum $locale = null): string
    {
        // Normalize locale if it's provided as an enum
        if ($locale instanceof LocaleEnum) {
            $locale = $locale->getLanguageCode();
        }

        // Set defaults if not provided
        $domain ??= 'messages';
        $locale ??= (string)$this->locale;

        // Add count to parameters so it can be used in formatted message
        $parameters['count'] = $count;

        // Prevent infinite recursion by tracking processed locales
        static $processedLocales = [];
        $localeKey = $locale . '|' . $domain . '|' . $key;

        if (isset($processedLocales[$localeKey])) {
            // Reset static variable for future calls and return fallback
            $processedLocales = [];
            return str_contains($key, '.') ? substr($key, strrpos($key, '.') + 1) : $key;
        }

        $processedLocales[$localeKey] = true;

        try {
            // Attempt to find the translation
            $translations = $this->findTranslation($key, $locale, $domain);

            // Add locale to parameters for proper formatting
            $formatterParams = $parameters;
            if (property_exists(IcuMessageFormatter::class, 'localeKey')) {
                $formatterParams[IcuMessageFormatter::localeKey] = $locale;
            }

            // Handle string translations (not plural forms)
            if (is_string($translations)) {
                $result = $this->formatter->format($translations, $formatterParams);
                $processedLocales = []; // Reset for future calls
                return $result;
            }

            // Handle array translations (plural forms)
            if (is_array($translations)) {
                // Get plural rule for the locale
                $pluralRule = $this->pluralRuleProvider->getRule($locale);
                $category = $pluralRule->getCategory($count);

                // Try to find the appropriate plural form
                if (isset($translations[$category->value])) {
                    $result = $this->formatter->format($translations[$category->value], $formatterParams);
                    $processedLocales = []; // Reset for future calls
                    return $result;
                }

                // Fall back to 'other' form if available
                if (isset($translations[PluralCategory::OTHER->value])) {
                    $result = $this->formatter->format($translations[PluralCategory::OTHER->value], $formatterParams);
                    $processedLocales = []; // Reset for future calls
                    return $result;
                }

                // Last resort: try first element if numerically indexed
                if (isset($translations[0])) {
                    $result = $this->formatter->format($translations[0], $formatterParams);
                    $processedLocales = []; // Reset for future calls
                    return $result;
                }
            }

            // No suitable translation found
            throw new TranslationNotFoundException($key, $locale, $domain);
        } catch (TranslationNotFoundException) {
            // Try with fallback locale if available and different from current
            if ($this->fallbackLocale !== null && (string)$this->fallbackLocale !== $locale) {
                $processedLocales = []; // Reset before recursion
                return $this->translatePlural($key, $count, $parameters, $domain, (string)$this->fallbackLocale);
            }

            // Reset the processed locales tracker and return fallback
            $processedLocales = [];
            return str_contains($key, '.') ? substr($key, strrpos($key, '.') + 1) : $key;
        }
    }

    /**
     * Finds a translation by key, locale, and domain.
     * Supports dot notation for accessing nested keys.
     *
     * @param string $key The translation key to find
     * @param string $locale The locale to search in
     * @param string $domain The domain to search in
     *
     * @return string|array<string, string> The translation (string or array for plural forms)
     * @throws TranslationNotFoundException If no translation is found
     */
    private function findTranslation(string $key, string $locale, string $domain): string|array
    {
        // Ensure messages are loaded for the locale and domain
        $this->loadMessagesIfNeeded($locale, $domain);

        // Check if the structure exists before accessing
        if (!isset($this->loadedMessages[$locale][$domain])) {
            throw new TranslationNotFoundException($key, $locale, $domain);
        }

        // Direct key lookup
        if (array_key_exists($key, $this->loadedMessages[$locale][$domain])) {
            return $this->loadedMessages[$locale][$domain][$key];
        }

        // Handle dot notation for nested keys
        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $current = $this->loadedMessages[$locale][$domain];

            foreach ($parts as $part) {
                if (!is_array($current) || !array_key_exists($part, $current)) {
                    throw new TranslationNotFoundException($key, $locale, $domain);
                }
                $current = $current[$part];
            }

            if (is_string($current) || is_array($current)) {
                return $current;
            }
        }

        throw new TranslationNotFoundException($key, $locale, $domain);
    }

    /**
     * Loads messages for a locale and domain if they're not already loaded.
     * Uses cache if available to improve performance.
     *
     * @param string $locale The locale to load messages for
     * @param string $domain The domain to load messages for
     */
    private function loadMessagesIfNeeded(string $locale, string $domain): void
    {
        // Skip if messages are already loaded
        if (isset($this->loadedMessages[$locale][$domain])) {
            return;
        }

        // Try to get messages from cache
        if ($this->cache !== null && $this->cache->has($locale, $domain)) {
            $this->loadedMessages[$locale][$domain] = $this->cache->get($locale, $domain) ?? [];
            return;
        }

        // Load messages from source
        if ($this->loader->exists($locale, $domain)) {
            $messages = $this->loader->load($locale, $domain);
            $this->loadedMessages[$locale][$domain] = $messages;

            // Store in cache if available
            if ($this->cache !== null) {
                $this->cache->set($locale, $domain, $messages);
            }
        } else {
            // No messages found, store empty array to prevent repeated loading attempts
            $this->loadedMessages[$locale][$domain] = [];
        }
    }
}