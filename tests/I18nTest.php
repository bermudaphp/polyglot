<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Cache\InMemoryCache;
use Bermuda\Polyglot\Detector\LocaleDetectorInterface;
use Bermuda\Polyglot\Formatter\IcuMessageFormatter;
use Bermuda\Polyglot\I18n;
use Bermuda\Polyglot\Loader\MessageLoaderInterface;
use Bermuda\Polyglot\Locale;
use Bermuda\Polyglot\LocaleEnum;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\Translator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(I18n::class)]
#[CoversClass(Translator::class)]
final class I18nTest extends TestCase
{
    private InMemoryCache $cache;
    private CldrPluralRuleProvider $pluralRuleProvider;
    private IcuMessageFormatter $formatter;
    private MessageLoaderInterface $loader;
    private Translator $translator;
    private I18n $i18n;
    private LocaleDetectorInterface $localeDetector;

    protected function setUp(): void
    {
        $this->loader = $this->createStub(MessageLoaderInterface::class);
        $this->pluralRuleProvider = new CldrPluralRuleProvider();
        $this->formatter = new IcuMessageFormatter($this->pluralRuleProvider);
        $this->cache = new InMemoryCache();

        // Create a real Translator instance
        $this->translator = new Translator(
            'en',
            'fr',
            $this->loader,
            $this->formatter,
            $this->pluralRuleProvider,
            $this->cache
        );

        // Create a localeDetector stub
        $this->localeDetector = $this->createStub(LocaleDetectorInterface::class);

        // Create I18n instance with real Translator
        $this->i18n = new I18n($this->translator, $this->localeDetector);
    }

    /**
     * Test basic I18n functionality for translation
     */
    public function testTranslate(): void
    {
        // Configure loader stub
        $this->loader->method('exists')->willReturn(true);
        $this->loader->method('load')->willReturn([
            'welcome' => 'Welcome!',
            'greeting' => 'Hello, {name}!'
        ]);

        // Test basic translation
        $this->assertEquals('Welcome!', $this->i18n->translate('welcome'));

        // Test with parameters
        $this->assertEquals('Hello, John!', $this->i18n->translate('greeting', ['name' => 'John']));

        // Test shorthand method
        $this->assertEquals('Welcome!', $this->i18n->t('welcome'));
    }

    /**
     * Test plural translation functionality
     */
    public function testTranslatePlural(): void
    {
        // Configure loader
        $this->loader->method('exists')->willReturn(true);
        $this->loader->method('load')->willReturn([
            'items' => [
                'one' => 'You have 1 item',
                'other' => 'You have {count} items'
            ]
        ]);

        // Test plural translation
        $this->assertEquals('You have 1 item', $this->i18n->translatePlural('items', 1));
        $this->assertEquals('You have 5 items', $this->i18n->translatePlural('items', 5));

        // Test shorthand method
        $this->assertEquals('You have 1 item', $this->i18n->tp('items', 1));
        $this->assertEquals('You have 5 items', $this->i18n->tp('items', 5));
    }

    /**
     * Test setting locale
     */
    public function testSetLocale(): void
    {
        // Configure loader for multiple locales
        $this->loader->method('exists')->willReturn(true);
        $this->loader->method('load')->willReturnMap([
            ['en', 'messages', [
                'greeting' => 'Hello'
            ]],
            ['fr', 'messages', [
                'greeting' => 'Bonjour'
            ]],
            ['de', 'messages', [
                'greeting' => 'Hallo'
            ]]
        ]);

        // Test initial locale
        $this->assertEquals('en', (string)$this->i18n->getLocale());

        // Test changing locale with string
        $this->i18n->setLocale('fr');
        $this->assertEquals('fr', (string)$this->i18n->getLocale());
        $this->assertEquals('Bonjour', $this->i18n->t('greeting'));

        // Test changing locale with Locale object
        $this->i18n->setLocale(new Locale('de'));
        $this->assertEquals('de', (string)$this->i18n->getLocale());
        $this->assertEquals('Hallo', $this->i18n->t('greeting'));

        // Test changing locale with LocaleEnum
        $this->i18n->setLocale(LocaleEnum::ENGLISH);
        $this->assertEquals('en', (string)$this->i18n->getLocale());
        $this->assertEquals('Hello', $this->i18n->t('greeting'));
    }

    /**
     * Test detecting locale
     */
    public function testDetectAndSetLocale(): void
    {
        // Configure detector to return a locale
        $this->localeDetector->method('detect')->willReturn('fr');

        // Configure loader
        $this->loader->method('exists')->willReturn(true);
        $this->loader->method('load')->willReturnMap([
            ['en', 'messages', [
                'greeting' => 'Hello'
            ]],
            ['fr', 'messages', [
                'greeting' => 'Bonjour'
            ]]
        ]);

        // Test detect and set locale
        $this->i18n->detectAndSetLocale();
        $this->assertEquals('fr', (string)$this->i18n->getLocale());
        $this->assertEquals('Bonjour', $this->i18n->t('greeting'));

        // Test detect and set locale with default
        $this->localeDetector = $this->createStub(LocaleDetectorInterface::class);
        $this->localeDetector->method('detect')->willReturn(null);
        $this->i18n = new I18n($this->translator, $this->localeDetector);

        $this->i18n->detectAndSetLocale('de');
        $this->assertEquals('de', (string)$this->i18n->getLocale());
    }

    /**
     * Test getting translator and detector
     */
    public function testGetTranslatorAndDetector(): void
    {
        $this->assertSame($this->translator, $this->i18n->getTranslator());
        $this->assertSame($this->localeDetector, $this->i18n->getLocaleDetector());
    }
}
