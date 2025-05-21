<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Detector\QueryLocaleDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(QueryLocaleDetector::class)]
class QueryLocaleDetectorTest extends TestCase
{
    #[Test]
    public function detectsLocaleFromQueryParam(): void
    {
        $_GET['locale'] = 'fr';

        $detector = new QueryLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function detectsLocaleFromRequestQueryParams(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')
            ->willReturn(['locale' => 'fr']);

        $detector = new QueryLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detectFromRequest($request);

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function usesCustomParamName(): void
    {
        $_GET['lang'] = 'fr';

        $detector = new QueryLocaleDetector(['en', 'fr', 'de'], 'lang');
        $locale = $detector->detect();

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function returnsNullForNoMatchingLocale(): void
    {
        $_GET['locale'] = 'it';

        $detector = new QueryLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertNull($locale);
    }

    #[Test]
    public function returnsNullForMissingParam(): void
    {
        $_GET = [];

        $detector = new QueryLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertNull($locale);
    }
}