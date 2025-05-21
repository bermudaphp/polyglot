# Polyglot

[![PHP Version Require](https://img.shields.io/badge/php-%3E%3D8.4-brightgreen.svg)](https://php.net/)
[![GitHub Tests](https://img.shields.io/github/actions/workflow/status/bermudaphp/polyglot/tests.yml?branch=master&label=tests)](https://github.com/bermudaphp/polyglot/actions/workflows/tests.yml)
[![Code Coverage](https://img.shields.io/endpoint?url=https://gist.githubusercontent.com/Shelamkoff/fa30b7e036077063fbed756e01a91934/raw/polyglot-coverage.json)](https://github.com/bermudaphp/polyglot/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/bermudaphp/polyglot.svg)](https://packagist.org/packages/bermudaphp/polyglot)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/bermudaphp/polyglot/blob/master/LICENSE)

Polyglot is a powerful, flexible internationalization (i18n) and localization (l10n) library for PHP 8.4+ applications with support for ICU message formatting, pluralization, caching, and more.

[Read documentation in Russian (Документация на русском)](README.RU.md)

## Table of Contents

- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Key Concepts](#key-concepts)
- [Creating a Translator](#creating-a-translator)
- [Locales](#locales)
- [Translation Files](#translation-files)
- [Pluralization](#pluralization)
- [ICU Message Format](#icu-message-format)
  - [Parameter Substitution](#parameter-substitution)
  - [Number Formatting](#number-formatting)
  - [Date Formatting](#date-formatting)
  - [Plural Formatting](#plural-formatting)
  - [Select Formatting](#select-formatting)
  - [Nested Formatting](#nested-formatting)
- [Message Builders](#message-builders)
  - [ICU Message Builder](#icu-message-builder)
  - [Plural Builder](#plural-builder)
  - [Select Builder](#select-builder)
- [Caching](#caching)
- [Locale Detection](#locale-detection)
- [PSR-15 Middleware](#psr-15-middleware)
- [License](#license)

## Installation

```bash
composer require bermudaphp/polyglot
```

## Basic Usage

```php
// Create an I18n instance using the factory
$i18n = I18nFactory::create('/path/to/translations', 'en', 'en');

// Basic translation
echo $i18n->translate('welcome'); // "Welcome!"

// Translation with parameters
echo $i18n->translate('greeting', ['name' => 'John']); // "Hello, John!"

// Translation with pluralization
echo $i18n->translatePlural('items', 1); // "You have 1 item"
echo $i18n->translatePlural('items', 5); // "You have 5 items"

// Shorthand methods
echo $i18n->t('welcome');
echo $i18n->tp('items', 3);

// Change locale
$i18n->setLocale('fr');
echo $i18n->t('welcome'); // "Bienvenue !"
```

## Key Concepts

Polyglot uses several core concepts:

- **Locale**: Identifies a specific language and region (e.g., 'en_US', 'fr_FR')
- **Domain**: Groups related translations (e.g., 'messages', 'errors', 'admin')
- **Translation Key**: Unique identifier for a translatable string
- **Fallback Locale**: Used when a translation is missing in the primary locale
- **Plurality**: Different forms of words based on quantity (e.g., "1 item" vs "5 items")
- **ICU Format**: International Components for Unicode message format for complex translations

## Creating a Translator

The simplest way to create a translator is using the factory:

```php
use Bermuda\Polyglot\I18nFactory;

$i18n = I18nFactory::create(
    resourcesPath: '/path/to/translations',
    defaultLocale: 'en',
    fallbackLocale: 'en',
    availableLocales: ['en', 'fr', 'de', 'es'],
    cache: null // Optional cache implementation
);
```

For more control, you can create components manually:

```php
use Bermuda\Polyglot\Translator;
use Bermuda\Polyglot\Loader\PhpArrayMessageLoader;
use Bermuda\Polyglot\Formatter\IcuMessageFormatter;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\Cache\InMemoryCache;

// Create loaders
$loader = new PhpArrayMessageLoader('/path/to/translations');

// Create formatters
$formatter = new IcuMessageFormatter(new CldrPluralRuleProvider());

// Create plural rule provider
$pluralRuleProvider = new CldrPluralRuleProvider();

// Create cache (optional)
$cache = new InMemoryCache();

// Create translator
$translator = new Translator(
    locale: 'en',
    fallbackLocale: 'en',
    loader: $loader,
    formatter: $formatter,
    pluralRuleProvider: $pluralRuleProvider,
    cache: $cache
);

// Create I18n instance
$i18n = new I18n($translator);
```

## Locales

Polyglot provides multiple ways to handle locale codes:

1. As simple strings ('en', 'en_US', 'fr_FR')
2. Using the `LocaleEnum` typed enum 
3. Using the `Locale` value object

### LocaleEnum

```php
use Bermuda\Polyglot\LocaleEnum;

$locale = LocaleEnum::ENGLISH_US;
$i18n->setLocale($locale);

// Get language code
$language = $locale->getLanguageCode(); // 'en'

// Get region code
$region = $locale->getRegionCode(); // 'US'

// Create from string
$locale = LocaleEnum::fromString('en-US'); // Handles both 'en-US' and 'en_US' formats
```

### Locale Value Object

```php
use Bermuda\Polyglot\Locale;

$locale = new Locale('en_US');

// Access components
echo $locale->language; // 'en'
echo $locale->region;   // 'US'
echo $locale->variant;  // null

// Convert to string
echo $locale; // 'en_US'

// Get fallbacks
$fallbacks = $locale->getFallbacks(); // ['en']
```

## Translation Files

Polyglot supports multiple formats for translation files:

### PHP Array Files

```php
// /path/to/translations/en/messages.php
return [
    'welcome' => 'Welcome!',
    'greeting' => 'Hello, {name}!',
    'items' => [
        'one' => 'You have 1 item',
        'other' => 'You have {count} items'
    ],
    'user' => [
        'greeting' => 'Welcome back, {name}!',
        'profile' => [
            'title' => 'User Profile'
        ]
    ]
];
```

### JSON Files

```json
{
    "welcome": "Welcome!",
    "greeting": "Hello, {name}!",
    "items": {
        "one": "You have 1 item",
        "other": "You have {count} items"
    },
    "user": {
        "greeting": "Welcome back, {name}!",
        "profile": {
            "title": "User Profile"
        }
    }
}
```

### Accessing Nested Keys

Nested keys can be accessed using dot notation:

```php
echo $i18n->t('user.profile.title'); // "User Profile"
```

## Pluralization

Polyglot provides robust support for pluralization based on the CLDR (Common Locale Data Repository) rules:

```php
// Translation file
return [
    'items' => [
        'one' => 'You have 1 item',
        'other' => 'You have {count} items'
    ],
    'apples' => [
        'one' => '{count} apple',
        'few' => '{count} apples', // For languages like Russian, Polish
        'many' => '{count} apples', // For languages like Russian, Polish
        'other' => '{count} apples'
    ]
];

// Usage
echo $i18n->translatePlural('items', 1); // "You have 1 item"
echo $i18n->translatePlural('items', 5); // "You have 5 items"

// With parameters
echo $i18n->translatePlural('items', 5, ['color' => 'red']); // "You have 5 red items"
```

## ICU Message Format

Polyglot supports the ICU (International Components for Unicode) message format, a powerful standard for handling translations with variables, pluralization, and more.

### Parameter Substitution

```php
// Translation
'greeting' => 'Hello, {name}!'

// Usage
echo $i18n->t('greeting', ['name' => 'John']); // "Hello, John!"
```

### Number Formatting

```php
// Translation
'price' => 'Price: {amount, number, currency}'

// Usage
echo $i18n->t('price', ['amount' => 1234.56]); // "Price: $1,234.56" (in en-US)
```

### Date Formatting

```php
// Translation
'today' => 'Today is {date, date, medium}'

// Usage
echo $i18n->t('today', ['date' => new DateTime()]); // "Today is Jan 1, 2023"
```

### Plural Formatting

```php
// Translation
'items' => '{count, plural, one{# item} other{# items}}'

// Usage
echo $i18n->t('items', ['count' => 1]); // "1 item"
echo $i18n->t('items', ['count' => 5]); // "5 items"
```

### Select Formatting

```php
// Translation
'gender' => '{gender, select, male{He} female{She} other{They}} will attend.'

// Usage
echo $i18n->t('gender', ['gender' => 'female']); // "She will attend."
```

### Nested Formatting

```php
// Translation
'nested' => '{gender, select, male{He has {count, plural, one{# apple} other{# apples}}} female{She has {count, plural, one{# apple} other{# apples}}} other{They have {count, plural, one{# apple} other{# apples}}}}'

// Usage
echo $i18n->t('nested', ['gender' => 'female', 'count' => 3]); // "She has 3 apples"
```

## Message Builders

Polyglot provides a set of builders to programmatically create ICU message templates.

### ICU Message Builder

```php
use Bermuda\Polyglot\Generator\IcuMessage;

// Create a message builder for a specific locale
$builder = IcuMessage::for('en');

// Create a simple message with placeholders
$message = $builder->message('Hello, {name}!');

// Create a gender-specific message
$gender = IcuMessage::gender(
    'gender',
    'He will attend', // male
    'She will attend', // female
    'They will attend' // other
);
```

### Plural Builder

```php
use Bermuda\Polyglot\Generator\IcuMessage;

// Create a plural builder for English
$pluralBuilder = IcuMessage::plural('count', 'en');

// Add cases manually
$pluralBuilder
    ->when('one', 'You have # item')
    ->when('other', 'You have # items');

// Get the ICU message
$icuMessage = $pluralBuilder->build();
// Result: "{count, plural, one{You have # item} other{You have # items}}"

// Using withInflections for common plurals
$builder = IcuMessage::plural('count', 'en')
    ->withInflections('You have # item', [
        'one' => '',
        'other' => 's'
    ]);

// For languages with more complex pluralization (e.g., Russian)
$builder = IcuMessage::plural('count', 'ru')
    ->withInflections('У вас # товар', [
        'one' => '',
        'few' => 'а',
        'many' => 'ов'
    ]);
```

### Select Builder

```php
use Bermuda\Polyglot\Generator\IcuMessage;

// Create a select builder
$selectBuilder = IcuMessage::select('role');

// Add cases
$selectBuilder
    ->when('admin', 'You have full access')
    ->when('manager', 'You have limited access')
    ->otherwise('You have basic access');

// Get the ICU message
$icuMessage = $selectBuilder->build();
// Result: "{role, select, admin{You have full access} manager{You have limited access} other{You have basic access}}"

// Using with nested plural
$builder = IcuMessage::select('gender')
    ->when('male', function($b) {
        return $b->plural('count', 'en')
            ->when('one', 'He has # apple')
            ->when('other', 'He has # apples');
    })
    ->when('female', function($b) {
        return $b->plural('count', 'en')
            ->when('one', 'She has # apple')
            ->when('other', 'She has # apples');
    });
```

## Caching

Polyglot supports caching of loaded translations to improve performance:

```php
use Bermuda\Polyglot\Cache\PsrCacheAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Create a PSR-16 compatible cache
$psr16Cache = new \Symfony\Component\Cache\Psr16Cache(
    new FilesystemAdapter()
);

// Create a cache adapter
$cache = new PsrCacheAdapter($psr16Cache);

// Use with I18n factory
$i18n = I18nFactory::create(
    resourcesPath: '/path/to/translations',
    defaultLocale: 'en',
    fallbackLocale: 'en',
    cache: $cache
);
```

Also included is an `InMemoryCache` for simple use cases:

```php
use Bermuda\Polyglot\Cache\InMemoryCache;

$cache = new InMemoryCache();
```

## Locale Detection

Polyglot can automatically detect the user's preferred locale:

```php
use Bermuda\Polyglot\Detector\HttpAcceptLanguageDetector;
use Bermuda\Polyglot\Detector\QueryLocaleDetector;
use Bermuda\Polyglot\Detector\PathLocaleDetector;
use Bermuda\Polyglot\Detector\LocaleDetectorChain;

// Available locales
$availableLocales = ['en', 'fr', 'de', 'es'];

// Create detectors
$httpDetector = new HttpAcceptLanguageDetector($availableLocales);
$queryDetector = new QueryLocaleDetector($availableLocales, 'locale');
$pathDetector = new PathLocaleDetector($availableLocales);

// Create a chain of detectors (checked in order)
$detector = new LocaleDetectorChain([
    $pathDetector,    // First check URL path (/en/page)
    $queryDetector,   // Then check query param (?locale=en)
    $httpDetector     // Finally check Accept-Language header
]);

// Use with I18n
$i18n = new I18n($translator, $detector);

// Detect and set locale
$i18n->detectAndSetLocale('en'); // 'en' is the default if no locale is detected
```

## PSR-15 Middleware

Polyglot includes a PSR-15 middleware for detecting the locale from HTTP requests:

```php
use Bermuda\Polyglot\I18nMiddleware;

// Create middleware
$middleware = I18nFactory::createMiddleware($i18n, 'en');

// Add to your middleware stack
$app->pipe($middleware);
```

The middleware:
1. Detects the locale from the request
2. Sets it in the I18n instance
3. Adds the locale as a request attribute
4. Passes control to the next middleware

## License

The Polyglot library is open-sourced software licensed under the [MIT license](LICENSE).
