<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\I18n;
use Bermuda\Polyglot\I18nFactory;
use Bermuda\Polyglot\I18nMiddleware;
use Bermuda\Polyglot\Locale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(I18nFactory::class)]
class I18nFactoryTest extends TestCase
{
    #[Test]
    public function createReturnsI18nInstance(): void
    {
        $resourcesPath = __DIR__ . '/resources/translations';

        $i18n = I18nFactory::create(
            $resourcesPath,
            'en',
            'ru',
            ['en', 'fr'],
        );

        $this->assertSame('en', $i18n->getLocale()->toString());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function createMiddlewareReturnsMiddlewareInstance(): void
    {
        $i18n = $this->createMock(I18n::class);
        $this->assertInstanceOf(I18nMiddleware::class, I18nFactory::createMiddleware($i18n, 'en'));
    }
}