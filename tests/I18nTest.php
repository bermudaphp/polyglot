<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Detector\LocaleDetectorChain;
use Bermuda\Polyglot\Exception\InvalidLocaleException;
use Bermuda\Polyglot\I18n;
use Bermuda\Polyglot\Locale;
use Bermuda\Polyglot\Translator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(I18n::class)]
class I18nTest extends TestCase
{
    private I18n $i18n;
    private MockObject $translator;
    private MockObject $localeDetector;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        $this->localeDetector = $this->createMock(LocaleDetectorChain::class);

        $this->i18n = new I18n($this->translator, $this->localeDetector);
    }

    #[Test]
    public function translateCallsTranslator(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('welcome', ['name' => 'John'], 'messages')
            ->willReturn('Welcome, John!');

        $result = $this->i18n->translate('welcome', ['name' => 'John'], 'messages');
        $this->assertSame('Welcome, John!', $result);
    }

    #[Test]
    public function tIsShorthandForTranslate(): void
    {
        $this->translator->expects($this->once())
            ->method('translate')
            ->with('welcome', ['name' => 'John'], 'messages')
            ->willReturn('Welcome, John!');

        $result = $this->i18n->t('welcome', ['name' => 'John'], 'messages');
        $this->assertSame('Welcome, John!', $result);
    }

    #[Test]
    public function translatePluralCallsTranslator(): void
    {
        $this->translator->expects($this->once())
            ->method('translatePlural')
            ->with('items', 5, ['price' => '$10'], 'shop')
            ->willReturn('5 items for $10');

        $result = $this->i18n->translatePlural('items', 5, ['price' => '$10'], 'shop');
        $this->assertSame('5 items for $10', $result);
    }

    #[Test]
    public function tpIsShorthandForTranslatePlural(): void
    {
        $this->translator->expects($this->once())
            ->method('translatePlural')
            ->with('items', 5, ['price' => '$10'], 'shop')
            ->willReturn('5 items for $10');

        $result = $this->i18n->tp('items', 5, ['price' => '$10'], 'shop');
        $this->assertSame('5 items for $10', $result);
    }

    #[Test]
    public function getLocaleCallsTranslator(): void
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn(new Locale('en'));

        $result = $this->i18n->getLocale();
        $this->assertSame('en', $result->toString());
    }

    /**
     * @throws InvalidLocaleException
     */
    #[Test]
    public function setLocaleCallsTranslator(): void
    {
        $this->translator->expects($this->once())
            ->method('setLocale')
            ->with('fr');

        $result = $this->i18n->setLocale('fr');
        $this->assertSame($this->i18n, $result);
    }

    #[Test]
    public function detectAndSetLocaleCallsDetector(): void
    {
        $this->localeDetector->expects($this->once())
            ->method('detect')
            ->willReturn('fr');

        $this->translator->expects($this->once())
            ->method('setLocale')
            ->with('fr');

        $result = $this->i18n->detectAndSetLocale();
        $this->assertSame($this->i18n, $result);
    }

    #[Test]
    public function detectAndSetLocaleUsesDefaultWhenNotDetected(): void
    {
        $this->localeDetector->expects($this->once())
            ->method('detect')
            ->willReturn(null);

        $this->translator->expects($this->once())
            ->method('setLocale')
            ->with('de');

        $result = $this->i18n->detectAndSetLocale('de');
        $this->assertSame($this->i18n, $result);
    }

    /**
     * @throws InvalidLocaleException
     * @throws Exception
     */
    #[Test]
    public function detectAndSetLocaleFromRequestCallsDetector(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $this->localeDetector->expects($this->once())
            ->method('detectFromRequest')
            ->with($request)
            ->willReturn('fr');

        $this->translator->expects($this->once())
            ->method('setLocale')
            ->with('fr');

        $result = $this->i18n->detectAndSetLocaleFromRequest($request);
        $this->assertSame($this->i18n, $result);
    }

    #[Test]
    public function getTranslatorReturnsTranslator(): void
    {
        $result = $this->i18n->getTranslator();
        $this->assertSame($this->translator, $result);
    }

    #[Test]
    public function getLocaleDetectorReturnsDetector(): void
    {
        $result = $this->i18n->getLocaleDetector();
        $this->assertSame($this->localeDetector, $result);
    }
}