<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Detector\HttpAcceptLanguageDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(HttpAcceptLanguageDetector::class)]
class HttpAcceptLanguageDetectorTest extends TestCase
{
    #[Test]
    public function detectsLocaleFromHeader(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7';

        $detector = new HttpAcceptLanguageDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function detectsLocaleFromRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->with('Accept-Language')
            ->willReturn('fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7');

        $detector = new HttpAcceptLanguageDetector(['en', 'fr', 'de']);
        $locale = $detector->detectFromRequest($request);

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function returnsNullForMissingHeader(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $detector = new HttpAcceptLanguageDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertNull($locale);
    }

    #[Test]
    public function returnsNullForEmptyHeaderInRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeaderLine')
            ->with('Accept-Language')
            ->willReturn('');

        $detector = new HttpAcceptLanguageDetector(['en', 'fr', 'de']);
        $locale = $detector->detectFromRequest($request);

        $this->assertNull($locale);
    }

    #[Test]
    public function returnsNullForNoMatchingLocale(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'it-IT,it;q=0.9';

        $detector = new HttpAcceptLanguageDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertNull($locale);
    }

    #[Test]
    public function respectsQualityValues(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR;q=0.7,en-US;q=0.9,de;q=0.8';

        $detector = new HttpAcceptLanguageDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertSame('en', $locale);
    }

    #[Test]
    public function exactMatchHasPriorityOverLanguageMatch(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';

        $detector = new HttpAcceptLanguageDetector(['en', 'en_US', 'fr']);
        $locale = $detector->detect();

        $this->assertSame('en_US', $locale);
    }
}