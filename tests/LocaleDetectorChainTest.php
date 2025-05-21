<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Detector\HttpAcceptLanguageDetector;
use Bermuda\Polyglot\Detector\LocaleDetectorChain;
use Bermuda\Polyglot\Detector\PathLocaleDetector;
use Bermuda\Polyglot\Detector\QueryLocaleDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(LocaleDetectorChain::class)]
class LocaleDetectorChainTest extends TestCase
{
    #[Test]
    public function usesFirstDetectorWithResult(): void
    {
        $detector1 = $this->createMock(HttpAcceptLanguageDetector::class);
        $detector1->method('detect')->willReturn(null);

        $detector2 = $this->createMock(PathLocaleDetector::class);
        $detector2->method('detect')->willReturn('fr');

        $detector3 = $this->createMock(QueryLocaleDetector::class);
        $detector3->expects($this->never())->method('detect');

        $chain = new LocaleDetectorChain([$detector1, $detector2, $detector3]);
        $locale = $chain->detect();

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function usesFirstDetectorWithResultFromRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $detector1 = $this->createMock(HttpAcceptLanguageDetector::class);
        $detector1->method('detectFromRequest')->willReturn(null);

        $detector2 = $this->createMock(PathLocaleDetector::class);
        $detector2->method('detectFromRequest')->willReturn('fr');

        $detector3 = $this->createMock(QueryLocaleDetector::class);
        $detector3->expects($this->never())->method('detectFromRequest');

        $chain = new LocaleDetectorChain([$detector1, $detector2, $detector3]);
        $locale = $chain->detectFromRequest($request);

        $this->assertSame('fr', $locale);
    }

    #[Test]
    public function returnsNullIfNoDetectorHasResult(): void
    {
        $detector1 = $this->createMock(HttpAcceptLanguageDetector::class);
        $detector1->method('detect')->willReturn(null);

        $detector2 = $this->createMock(PathLocaleDetector::class);
        $detector2->method('detect')->willReturn(null);

        $chain = new LocaleDetectorChain([$detector1, $detector2]);
        $locale = $chain->detect();

        $this->assertNull($locale);
    }

    #[Test]
    public function canAddDetectorToChain(): void
    {
        $detector1 = $this->createMock(HttpAcceptLanguageDetector::class);
        $detector1->method('detect')->willReturn(null);

        $chain = new LocaleDetectorChain([$detector1]);

        $detector2 = $this->createMock(PathLocaleDetector::class);
        $detector2->method('detect')->willReturn('fr');

        $chain->addDetector($detector2);

        $locale = $chain->detect();
        $this->assertSame('fr', $locale);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function usesRegularDetectForNonPsrDetectors(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        // Create a non-PSR detector (doesn't implement PsrRequestAwareLocaleDetectorInterface)
        $detector = $this->createMock(\Bermuda\Polyglot\Detector\LocaleDetectorInterface::class);
        $detector->method('detect')->willReturn('fr');

        $chain = new LocaleDetectorChain([$detector]);
        $locale = $chain->detectFromRequest($request);

        $this->assertSame('fr', $locale);
    }
}