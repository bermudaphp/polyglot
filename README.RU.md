# Polyglot

[![PHP Version Require](https://img.shields.io/badge/php-%3E%3D8.4-brightgreen.svg)](https://php.net/)
[![GitHub Tests](https://img.shields.io/github/actions/workflow/status/bermudaphp/polyglot/tests.yml?branch=master&label=tests)](https://github.com/bermudaphp/polyglot/actions/workflows/tests.yml)
[![Code Coverage](https://codecov.io/gh/bermudaphp/polyglot/branch/master/graph/badge.svg)](https://codecov.io/gh/bermudaphp/polyglot)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/bermudaphp/polyglot.svg)](https://packagist.org/packages/bermudaphp/polyglot)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/bermudaphp/polyglot/blob/master/LICENSE)

Polyglot — это мощная и гибкая библиотека для интернационализации (i18n) и локализации (l10n) PHP 8.4+ приложений с поддержкой форматирования сообщений ICU, множественного числа, кэширования и многого другого.

[Read documentation in English (Документация на английском)](README.md)

## Содержание

- [Установка](#установка)
- [Базовое использование](#базовое-использование)
- [Ключевые концепции](#ключевые-концепции)
- [Создание Translator](#создание-translator)
- [Локали](#локали)
- [Файлы переводов](#файлы-переводов)
- [Множественное число](#множественное-число)
- [Формат сообщений ICU](#формат-сообщений-icu)
  - [Подстановка параметров](#подстановка-параметров)
  - [Форматирование чисел](#форматирование-чисел)
  - [Форматирование дат](#форматирование-дат)
  - [Множественное число](#множественное-число-1)
  - [Выбор по параметру (Select)](#выбор-по-параметру-select)
  - [Вложенное форматирование](#вложенное-форматирование)
- [Построители сообщений](#построители-сообщений)
  - [Построитель сообщений ICU](#построитель-сообщений-icu)
  - [Построитель множественного числа](#построитель-множественного-числа)
  - [Построитель выбора](#построитель-выбора)
- [Кэширование](#кэширование)
- [Определение локали](#определение-локали)
- [PSR-15 Middleware](#psr-15-middleware)
- [Лицензия](#лицензия)

## Установка

```bash
composer require bermudaphp/polyglot
```

## Базовое использование

```php
// Создание экземпляра I18n с использованием фабрики
$i18n = I18nFactory::create('/путь/к/переводам', 'ru', 'en');

// Базовый перевод
echo $i18n->translate('welcome'); // "Добро пожаловать!"

// Перевод с параметрами
echo $i18n->translate('greeting', ['name' => 'Иван']); // "Привет, Иван!"

// Перевод с множественным числом
echo $i18n->translatePlural('items', 1); // "У вас 1 товар"
echo $i18n->translatePlural('items', 5); // "У вас 5 товаров"

// Сокращенные методы
echo $i18n->t('welcome');
echo $i18n->tp('items', 3);

// Изменение локали
$i18n->setLocale('en');
echo $i18n->t('welcome'); // "Welcome!"
```

## Ключевые концепции

Polyglot использует несколько ключевых концепций:

- **Локаль**: Идентифицирует конкретный язык и регион (например, 'ru_RU', 'en_US')
- **Домен**: Группирует связанные переводы (например, 'messages', 'errors', 'admin')
- **Ключ перевода**: Уникальный идентификатор для переводимой строки
- **Запасная локаль**: Используется, когда перевод отсутствует в основной локали
- **Множественное число**: Различные формы слов в зависимости от количества (например, "1 товар" vs "5 товаров")
- **Формат ICU**: Формат сообщений International Components for Unicode для сложных переводов

## Создание Translator

Самый простой способ создать переводчик — использовать фабрику:

```php
use Bermuda\Polyglot\I18nFactory;

$i18n = I18nFactory::create(
    resourcesPath: '/путь/к/переводам',
    defaultLocale: 'ru',
    fallbackLocale: 'en',
    availableLocales: ['ru', 'en', 'de', 'fr'],
    cache: null // Опциональная реализация кэша
);
```

Для большего контроля можно создать компоненты вручную:

```php
use Bermuda\Polyglot\Translator;
use Bermuda\Polyglot\Loader\PhpArrayMessageLoader;
use Bermuda\Polyglot\Formatter\IcuMessageFormatter;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\Cache\InMemoryCache;

// Создание загрузчика
$loader = new PhpArrayMessageLoader('/путь/к/переводам');

// Создание форматтера
$formatter = new IcuMessageFormatter(new CldrPluralRuleProvider());

// Создание провайдера правил множественного числа
$pluralRuleProvider = new CldrPluralRuleProvider();

// Создание кэша (опционально)
$cache = new InMemoryCache();

// Создание переводчика
$translator = new Translator(
    locale: 'ru',
    fallbackLocale: 'en',
    loader: $loader,
    formatter: $formatter,
    pluralRuleProvider: $pluralRuleProvider,
    cache: $cache
);

// Создание экземпляра I18n
$i18n = new I18n($translator);
```

## Локали

Polyglot предоставляет несколько способов работы с кодами локалей:

1. Как простые строки ('ru', 'ru_RU', 'en_US')
2. Используя типизированный enum `LocaleEnum`
3. Используя value object `Locale`

### LocaleEnum

```php
use Bermuda\Polyglot\LocaleEnum;

$locale = LocaleEnum::RUSSIAN_RU;
$i18n->setLocale($locale);

// Получение кода языка
$language = $locale->getLanguageCode(); // 'ru'

// Получение кода региона
$region = $locale->getRegionCode(); // 'RU'

// Создание из строки
$locale = LocaleEnum::fromString('ru-RU'); // Работает как с 'ru-RU', так и с 'ru_RU'
```

### Объект Locale

```php
use Bermuda\Polyglot\Locale;

$locale = new Locale('ru_RU');

// Доступ к компонентам
echo $locale->language; // 'ru'
echo $locale->region;   // 'RU'
echo $locale->variant;  // null

// Преобразование в строку
echo $locale; // 'ru_RU'

// Получение запасных локалей
$fallbacks = $locale->getFallbacks(); // ['ru']
```

## Файлы переводов

Polyglot поддерживает несколько форматов файлов переводов:

### PHP Array файлы

```php
// /путь/к/переводам/ru/messages.php
return [
    'welcome' => 'Добро пожаловать!',
    'greeting' => 'Привет, {name}!',
    'items' => [
        'one' => 'У вас {count} товар',
        'few' => 'У вас {count} товара',
        'many' => 'У вас {count} товаров',
        'other' => 'У вас {count} товаров'
    ],
    'user' => [
        'greeting' => 'С возвращением, {name}!',
        'profile' => [
            'title' => 'Профиль пользователя'
        ]
    ]
];
```

### JSON файлы

```json
{
    "welcome": "Добро пожаловать!",
    "greeting": "Привет, {name}!",
    "items": {
        "one": "У вас {count} товар",
        "few": "У вас {count} товара",
        "many": "У вас {count} товаров",
        "other": "У вас {count} товаров"
    },
    "user": {
        "greeting": "С возвращением, {name}!",
        "profile": {
            "title": "Профиль пользователя"
        }
    }
}
```

### Доступ к вложенным ключам

Доступ к вложенным ключам осуществляется через точечную нотацию:

```php
echo $i18n->t('user.profile.title'); // "Профиль пользователя"
```

## Множественное число

Polyglot предоставляет надежную поддержку множественного числа на основе правил CLDR (Common Locale Data Repository):

```php
// Файл перевода
return [
    'items' => [
        'one' => 'У вас {count} товар',
        'few' => 'У вас {count} товара',
        'many' => 'У вас {count} товаров',
        'other' => 'У вас {count} товаров'
    ],
    'apples' => [
        'one' => '{count} яблоко',
        'few' => '{count} яблока',
        'many' => '{count} яблок',
        'other' => '{count} яблок'
    ]
];

// Использование
echo $i18n->translatePlural('items', 1); // "У вас 1 товар"
echo $i18n->translatePlural('items', 3); // "У вас 3 товара"
echo $i18n->translatePlural('items', 5); // "У вас 5 товаров"

// С параметрами
echo $i18n->translatePlural('items', 5, ['color' => 'красных']); // "У вас 5 красных товаров"
```

## Формат сообщений ICU

Polyglot поддерживает формат сообщений ICU (International Components for Unicode) — мощный стандарт для обработки переводов с переменными, множественным числом и многим другим.

### Подстановка параметров

```php
// Перевод
'greeting' => 'Привет, {name}!'

// Использование
echo $i18n->t('greeting', ['name' => 'Иван']); // "Привет, Иван!"
```

### Форматирование чисел

```php
// Перевод
'price' => 'Цена: {amount, number, currency}'

// Использование
echo $i18n->t('price', ['amount' => 1234.56]); // "Цена: 1 234,56 ₽" (в ru-RU)
```

### Форматирование дат

```php
// Перевод
'today' => 'Сегодня {date, date, medium}'

// Использование
echo $i18n->t('today', ['date' => new DateTime()]); // "Сегодня 1 янв. 2023 г."
```

### Множественное число

```php
// Перевод
'items' => '{count, plural, one{# товар} few{# товара} many{# товаров} other{# товаров}}'

// Использование
echo $i18n->t('items', ['count' => 1]); // "1 товар"
echo $i18n->t('items', ['count' => 3]); // "3 товара"
echo $i18n->t('items', ['count' => 5]); // "5 товаров"
```

### Выбор по параметру (Select)

```php
// Перевод
'gender' => '{gender, select, male{Он} female{Она} other{Они}} будет присутствовать.'

// Использование
echo $i18n->t('gender', ['gender' => 'female']); // "Она будет присутствовать."
```

### Вложенное форматирование

```php
// Перевод
'nested' => '{gender, select, male{У него {count, plural, one{# яблоко} few{# яблока} many{# яблок} other{# яблок}}} female{У неё {count, plural, one{# яблоко} few{# яблока} many{# яблок} other{# яблок}}} other{У них {count, plural, one{# яблоко} few{# яблока} many{# яблок} other{# яблок}}}}'

// Использование
echo $i18n->t('nested', ['gender' => 'female', 'count' => 3]); // "У неё 3 яблока"
```

## Построители сообщений

Polyglot предоставляет набор построителей для программного создания шаблонов сообщений ICU.

### Построитель сообщений ICU

```php
use Bermuda\Polyglot\Generator\IcuMessage;

// Создание построителя сообщений для конкретной локали
$builder = IcuMessage::for('ru');

// Создание простого сообщения с заполнителями
$message = $builder->message('Привет, {name}!');

// Создание сообщения с учетом пола
$gender = IcuMessage::gender(
    'gender',
    'Он будет присутствовать', // male
    'Она будет присутствовать', // female
    'Они будут присутствовать' // other
);
```

### Построитель множественного числа

```php
use Bermuda\Polyglot\Generator\IcuMessage;

// Создание построителя множественного числа для русского языка
$pluralBuilder = IcuMessage::plural('count', 'ru');

// Добавление вариантов вручную
$pluralBuilder
    ->when('one', 'У вас # товар')
    ->when('few', 'У вас # товара')
    ->when('many', 'У вас # товаров')
    ->otherwise('У вас # товаров');

// Получение ICU сообщения
$icuMessage = $pluralBuilder->build();
// Результат: "{count, plural, one{У вас # товар} few{У вас # товара} many{У вас # товаров} other{У вас # товаров}}"

// Использование withInflections для стандартных форм множественного числа
$builder = IcuMessage::plural('count', 'ru')
    ->withInflections('У вас # товар', [
        'one' => '',
        'few' => 'а',
        'many' => 'ов',
        'other' => 'ов'
    ]);

// Для английского (проще)
$builder = IcuMessage::plural('count', 'en')
    ->withInflections('You have # item', [
        'one' => '',
        'other' => 's'
    ]);
```

### Построитель выбора

```php
use Bermuda\Polyglot\Generator\IcuMessage;

// Создание построителя выбора
$selectBuilder = IcuMessage::select('role');

// Добавление вариантов
$selectBuilder
    ->when('admin', 'У вас полный доступ')
    ->when('manager', 'У вас ограниченный доступ')
    ->otherwise('У вас базовый доступ');

// Получение ICU сообщения
$icuMessage = $selectBuilder->build();
// Результат: "{role, select, admin{У вас полный доступ} manager{У вас ограниченный доступ} other{У вас базовый доступ}}"

// Использование со вложенным множественным числом
$builder = IcuMessage::select('gender')
    ->when('male', function($b) {
        return $b->plural('count', 'ru')
            ->when('one', 'У него # яблоко')
            ->when('few', 'У него # яблока')
            ->when('many', 'У него # яблок')
            ->when('other', 'У него # яблок');
    })
    ->when('female', function($b) {
        return $b->plural('count', 'ru')
            ->when('one', 'У неё # яблоко')
            ->when('few', 'У неё # яблока')
            ->when('many', 'У неё # яблок')
            ->when('other', 'У неё # яблок');
    });
```

## Кэширование

Polyglot поддерживает кэширование загруженных переводов для повышения производительности:

```php
use Bermuda\Polyglot\Cache\PsrCacheAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Создание PSR-16 совместимого кэша
$psr16Cache = new \Symfony\Component\Cache\Psr16Cache(
    new FilesystemAdapter()
);

// Создание адаптера кэша
$cache = new PsrCacheAdapter($psr16Cache);

// Использование с фабрикой I18n
$i18n = I18nFactory::create(
    resourcesPath: '/путь/к/переводам',
    defaultLocale: 'ru',
    fallbackLocale: 'en',
    cache: $cache
);
```

Также включен `InMemoryCache` для простых случаев:

```php
use Bermuda\Polyglot\Cache\InMemoryCache;

$cache = new InMemoryCache();
```

## Определение локали

Polyglot может автоматически определять предпочтительную локаль пользователя:

```php
use Bermuda\Polyglot\Detector\HttpAcceptLanguageDetector;
use Bermuda\Polyglot\Detector\QueryLocaleDetector;
use Bermuda\Polyglot\Detector\PathLocaleDetector;
use Bermuda\Polyglot\Detector\LocaleDetectorChain;

// Доступные локали
$availableLocales = ['ru', 'en', 'de', 'fr'];

// Создание детекторов
$httpDetector = new HttpAcceptLanguageDetector($availableLocales);
$queryDetector = new QueryLocaleDetector($availableLocales, 'locale');
$pathDetector = new PathLocaleDetector($availableLocales);

// Создание цепочки детекторов (проверяются по порядку)
$detector = new LocaleDetectorChain([
    $pathDetector,    // Сначала проверяем путь URL (/ru/page)
    $queryDetector,   // Затем проверяем параметр запроса (?locale=ru)
    $httpDetector     // Наконец проверяем заголовок Accept-Language
]);

// Использование с I18n
$i18n = new I18n($translator, $detector);

// Определение и установка локали
$i18n->detectAndSetLocale('ru'); // 'ru' - это локаль по умолчанию, если не удалось определить
```

## PSR-15 Middleware

Polyglot включает PSR-15 middleware для определения локали из HTTP-запросов:

```php
use Bermuda\Polyglot\I18nMiddleware;

// Создание middleware
$middleware = I18nFactory::createMiddleware($i18n, 'ru');

// Добавление в конвейер middleware
$app->pipe($middleware);
```

Middleware:
1. Определяет локаль из запроса
2. Устанавливает её в экземпляре I18n
3. Добавляет локаль как атрибут запроса
4. Передает управление следующему middleware

## Лицензия

Библиотека Polyglot распространяется под [лицензией MIT](LICENSE).
