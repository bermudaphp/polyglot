<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Detector\PathLocaleDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(PathLocaleDetector::class)]
class PathLocaleDetectorTest extends TestCase
{
    #[Test]
    public function detectsLocaleFromPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/fr/some/page';

        $detector = new PathLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function detectsLocaleFromRequestUri(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')
            ->willReturn('/fr/some/page');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')
            ->willReturn($uri);

        $detector = new PathLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detectFromRequest($request);

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function respectsPathPrefix(): void
    {
        $_SERVER['REQUEST_URI'] = '/app/fr/some/page';

        $detector = new PathLocaleDetector(['en', 'fr', 'de'], '/app');
        $locale = $detector->detect();

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function returnsNullForNoMatchingLocale(): void
    {
        $_SERVER['REQUEST_URI'] = '/it/some/page';

        $detector = new PathLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertNull($locale);
    }

    #[Test]
    public function returnsNullForMissingRequestUri(): void
    {
        unset($_SERVER['REQUEST_URI']);

        $detector = new PathLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertNull($locale);
    }

    #[Test]
    public function returnsNullForEmptyPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $detector = new PathLocaleDetector(['en', 'fr', 'de']);
        $locale = $detector->detect();

        $this->assertNull($locale);
    }
}