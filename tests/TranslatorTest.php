<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Cache\InMemoryCache;
use Bermuda\Polyglot\Formatter\IcuMessageFormatter;
use Bermuda\Polyglot\Loader\MessageLoaderInterface;
use Bermuda\Polyglot\Locale;
use Bermuda\Polyglot\LocaleEnum;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;
use Bermuda\Polyglot\Translator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Translator::class)]
final class TranslatorTest extends TestCase
{
    private MessageLoaderInterface $loader;
    private IcuMessageFormatter $formatter;
    private PluralRuleProviderInterface $pluralRuleProvider;
    private InMemoryCache $cache;

    /**
     * Set up test dependencies with real objects instead of mocks where possible
     */
    protected function setUp(): void
    {
        $this->loader = $this->createMock(MessageLoaderInterface::class);
        $this->pluralRuleProvider = new CldrPluralRuleProvider();
        $this->formatter = new IcuMessageFormatter($this->pluralRuleProvider);
        $this->cache = new InMemoryCache();
    }

    /**
     * Test basic translator instantiation with different locale formats
     */
    public function testConstructorWithDifferentLocaleFormats(): void
    {
        // Test with string locale
        $translator = new Translator(
            'en_US',
            'en',
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        $this->assertEquals('en_US', (string)$translator->locale);
        $this->assertEquals('en', (string)$translator->fallbackLocale);

        // Test with LocaleEnum
        $translator = new Translator(
            LocaleEnum::ENGLISH_US,
            LocaleEnum::ENGLISH,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        $this->assertEquals('en_US', (string)$translator->locale);
        $this->assertEquals('en', (string)$translator->fallbackLocale);

        // Test with Locale object
        $translator = new Translator(
            new Locale('fr_FR'),
            new Locale('fr'),
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        $this->assertEquals('fr_FR', (string)$translator->locale);
        $this->assertEquals('fr', (string)$translator->fallbackLocale);

        // Test with null fallback
        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        $this->assertEquals('en', (string)$translator->locale);
        $this->assertNull($translator->fallbackLocale);
    }

    /**
     * Test basic translation functionality with direct key lookup
     */
    public function testBasicTranslation(): void
    {
        // Configure loader to return test messages
        $this->loader->method('exists')
            ->with('en', 'messages')
            ->willReturn(true);

        $this->loader->method('load')
            ->with('en', 'messages')
            ->willReturn([
                'welcome' => 'Welcome!',
                'goodbye' => 'Goodbye!',
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Test basic translations
        $this->assertEquals('Welcome!', $translator->translate('welcome'));
        $this->assertEquals('Goodbye!', $translator->translate('goodbye'));
    }

    /**
     * Test translation with parameter substitution
     */
    public function testTranslationWithParameters(): void
    {
        // Configure loader
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturn([
                'greeting' => 'Hello, {name}!',
                'welcome' => 'Welcome to {site}!',
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Test parameter substitution
        $this->assertEquals(
            'Hello, John!',
            $translator->translate('greeting', ['name' => 'John'])
        );

        $this->assertEquals(
            'Welcome to Example.com!',
            $translator->translate('welcome', ['site' => 'Example.com'])
        );
    }

    /**
     * Test translations with dot notation for nested keys
     */
    public function testNestedTranslation(): void
    {
        // Configure loader with nested messages
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturn([
                'user' => [
                    'greeting' => 'Hello, user!',
                    'profile' => [
                        'title' => 'User Profile',
                        'subtitle' => 'Personal Information'
                    ]
                ],
                'admin' => [
                    'dashboard' => 'Admin Dashboard'
                ]
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Test nested keys with dot notation
        $this->assertEquals('Hello, user!', $translator->translate('user.greeting'));
        $this->assertEquals('User Profile', $translator->translate('user.profile.title'));
        $this->assertEquals('Personal Information', $translator->translate('user.profile.subtitle'));
        $this->assertEquals('Admin Dashboard', $translator->translate('admin.dashboard'));
    }

    /**
     * Test fallback locale behavior when translations are missing
     */
    public function testFallbackLocale(): void
    {
        // Configure loader for primary locale (missing some translations)
        $this->loader->method('exists')
            ->willReturnMap([
                ['en_US', 'messages', true],
                ['en', 'messages', true],
            ]);

        $this->loader->method('load')
            ->willReturnMap([
                ['en_US', 'messages', [
                    'welcome' => 'Welcome to the US site!',
                    // Note: 'goodbye' is missing in en_US
                ]],
                ['en', 'messages', [
                    'welcome' => 'Welcome!',
                    'goodbye' => 'Goodbye!',
                ]],
            ]);

        $translator = new Translator(
            'en_US',
            'en',
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Should use en_US translation when available
        $this->assertEquals('Welcome to the US site!', $translator->translate('welcome'));

        // Should fall back to 'en' when translation is missing in en_US
        $this->assertEquals('Goodbye!', $translator->translate('goodbye'));

        // Non-existent key in both locales should return key name
        $this->assertEquals('unknown', $translator->translate('unknown'));
    }

    /**
     * Test translation with explicit locale parameter
     */
    public function testTranslateWithExplicitLocale(): void
    {
        // Configure loader
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturnMap([
                ['en', 'messages', [
                    'greeting' => 'Hello',
                ]],
                ['fr', 'messages', [
                    'greeting' => 'Bonjour',
                ]],
                ['de', 'messages', [
                    'greeting' => 'Hallo',
                ]],
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Default locale (en)
        $this->assertEquals('Hello', $translator->translate('greeting'));

        // Explicit locale parameter
        $this->assertEquals('Bonjour', $translator->translate('greeting', [], null, 'fr'));
        $this->assertEquals('Hallo', $translator->translate('greeting', [], null, 'de'));

        // With LocaleEnum
        $this->assertEquals('Bonjour', $translator->translate('greeting', [], null, LocaleEnum::FRENCH));
    }

    /**
     * Test translation with custom domain
     */
    public function testTranslateWithCustomDomain(): void
    {
        // Configure loader
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturnMap([
                ['en', 'messages', [
                    'greeting' => 'Hello',
                ]],
                ['en', 'admin', [
                    'greeting' => 'Hello admin',
                ]],
                ['en', 'errors', [
                    'not_found' => 'Page not found',
                ]],
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Default domain (messages)
        $this->assertEquals('Hello', $translator->translate('greeting'));

        // Custom domains
        $this->assertEquals('Hello admin', $translator->translate('greeting', [], 'admin'));
        $this->assertEquals('Page not found', $translator->translate('not_found', [], 'errors'));
    }

    /**
     * Test plural translation functionality
     */
    public function testTranslatePlural(): void
    {
        // Configure loader with pluralized messages
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturnMap([
                ['en', 'messages', [
                    'items' => [
                        'one' => 'You have 1 item',
                        'other' => 'You have {count} items'
                    ]
                ]],
                ['ru', 'messages', [
                    'apples' => [
                        'one' => 'Одно яблоко',
                        'few' => '{count} яблока',
                        'many' => '{count} яблок',
                        'other' => '{count} яблок'
                    ]
                ]]
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Test English plurals (uses real CLDR rules)
        $this->assertEquals('You have 1 item', $translator->translatePlural('items', 1));
        $this->assertEquals('You have 2 items', $translator->translatePlural('items', 2));
        $this->assertEquals('You have 5 items', $translator->translatePlural('items', 5));

        // Test Russian plurals (uses real CLDR rules)
        $translator->locale = 'ru';
        $this->assertEquals('Одно яблоко', $translator->translatePlural('apples', 1));
        $this->assertEquals('2 яблока', $translator->translatePlural('apples', 2));
        $this->assertEquals('3 яблока', $translator->translatePlural('apples', 3));
        $this->assertEquals('4 яблока', $translator->translatePlural('apples', 4));
        $this->assertEquals('5 яблок', $translator->translatePlural('apples', 5));
        $this->assertEquals('11 яблок', $translator->translatePlural('apples', 11));

        // Additional tests to verify CLDR rules correctness
        $this->assertEquals('0 яблок', $translator->translatePlural('apples', 0));
        $this->assertEquals('Одно яблоко', $translator->translatePlural('apples', 21));
        $this->assertEquals('22 яблока', $translator->translatePlural('apples', 22));
        $this->assertEquals('25 яблок', $translator->translatePlural('apples', 25));
        $this->assertEquals('111 яблок', $translator->translatePlural('apples', 111));
        $this->assertEquals('102 яблока', $translator->translatePlural('apples', 102));
    }

    /**
     * Test translation with cache integration using real InMemoryCache
     */
    public function testTranslationWithCache(): void
    {
        // First setup the loader to return data when called
        $this->loader->method('exists')
            ->with('en', 'messages')
            ->willReturn(true);

        $this->loader->method('load')
            ->with('en', 'messages')
            ->willReturn([
                'welcome' => 'Welcome!',
                'goodbye' => 'Goodbye!',
            ]);

        // Create a new translator instance with the cache
        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider,
            $this->cache
        );

        // First call, should load from source and cache
        $this->assertEquals('Welcome!', $translator->translate('welcome'));

        // Verify the cache has been populated
        $this->assertTrue($this->cache->has('en', 'messages'));
        $this->assertEquals(
            ['welcome' => 'Welcome!', 'goodbye' => 'Goodbye!'],
            $this->cache->get('en', 'messages')
        );

        // Create a spy loader that will fail the test if load() is called
        $spyLoader = $this->createMock(MessageLoaderInterface::class);
        $spyLoader->method('exists')->willReturn(true);
        $spyLoader->expects($this->never())->method('load');

        // Create a new translator with the spy loader but the same cache
        $translator2 = new Translator(
            'en',
            null,
            $spyLoader,
            $this->formatter,
            $this->pluralRuleProvider,
            $this->cache
        );

        // Should retrieve from cache without calling loader again
        $this->assertEquals('Welcome!', $translator2->translate('welcome'));
    }

    /**
     * Test handling of non-existent translations
     */
    public function testNonExistentTranslation(): void
    {
        // Loader returns empty message set
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturn([]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Non-existent key should return the key name
        $this->assertEquals('missing', $translator->translate('missing'));

        // Non-existent nested key should return the last segment
        $this->assertEquals('message', $translator->translate('user.greeting.message'));
    }

    /**
     * Test handling of non-existent translations with fallback
     */
    public function testNonExistentTranslationWithFallback(): void
    {
        // Primary locale has no translations
        $this->loader->method('exists')
            ->willReturnMap([
                ['fr', 'messages', true],
                ['en', 'messages', true],
            ]);

        $this->loader->method('load')
            ->willReturnMap([
                ['fr', 'messages', []],
                ['en', 'messages', [
                    'greeting' => 'Hello',
                ]],
            ]);

        $translator = new Translator(
            'fr',
            'en',
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Should fall back to English
        $this->assertEquals('Hello', $translator->translate('greeting'));

        // Missing in both languages should return key
        $this->assertEquals('missing', $translator->translate('missing'));
    }

    /**
     * Test handling of non-existent resources
     */
    public function testNonExistentResource(): void
    {
        // Resource does not exist
        $this->loader->method('exists')
            ->willReturn(false);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Non-existent resource should return key
        $this->assertEquals('greeting', $translator->translate('greeting'));
    }

    /**
     * Test for protection against infinite recursion in fallback chain
     */
    public function testInfiniteRecursionProtection(): void
    {
        // Both locales have no translation
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturn([]);

        // Circular fallback (should never happen in production but test anyway)
        $translator = new Translator(
            'en',
            'fr',
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Should not hang and just return the key
        $this->assertEquals('missing', $translator->translate('missing'));

        // Set up a self-referential fallback (en → en)
        $translator->locale = 'en';
        $translator->fallbackLocale = 'en';

        // Should not hang and just return the key
        $this->assertEquals('missing', $translator->translate('missing'));
    }

    /**
     * Test string translation handling in translatePlural
     */
    public function testTranslatePluralWithStringTranslation(): void
    {
        // Configure loader with a string instead of plural array
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturn([
                'items' => 'You have some items',
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Should handle string translations in translatePlural
        $this->assertEquals('You have some items', $translator->translatePlural('items', 5));
    }

    /**
     * Test array translation handling in translate
     */
    public function testTranslateWithArrayTranslation(): void
    {
        // Configure loader with plural array for regular translate call
        $this->loader->method('exists')
            ->willReturn(true);

        $this->loader->method('load')
            ->willReturn([
                'items' => [
                    'one' => 'One item',
                    'other' => 'Multiple items'
                ],
            ]);

        $translator = new Translator(
            'en',
            null,
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Should take the "other" form by default
        $this->assertEquals('Multiple items', $translator->translate('items'));
    }

    /**
     * Test changing locale and fallback on existing translator
     */
    public function testSetLocaleAndFallback(): void
    {
        $translator = new Translator(
            'en',
            'en',
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider
        );

        // Test initial state
        $this->assertEquals('en', (string)$translator->locale);

        // Test setting locale with string
        $translator->locale = 'fr';
        $this->assertEquals('fr', (string)$translator->locale);

        // Test setting locale with enum
        $translator->locale = LocaleEnum::GERMAN;
        $this->assertEquals('de', (string)$translator->locale);

        // Test setting locale with Locale object
        $translator->locale = new Locale('it');
        $this->assertEquals('it', (string)$translator->locale);

        // Test setting fallback locale
        $translator->fallbackLocale = 'es';
        $this->assertEquals('es', (string)$translator->fallbackLocale);

        // Test resetting fallback locale
        $translator->fallbackLocale = null;
        $this->assertNull($translator->fallbackLocale);
    }
}