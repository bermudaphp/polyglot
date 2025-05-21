<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Exception\InvalidLocaleException;
use Bermuda\Polyglot\Locale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Locale::class)]
class LocaleTest extends TestCase
{
    #[Test]
    public function validLocaleCreatesSuccessfully(): void
    {
        $locale = new Locale('en_US');
        $this->assertSame('en', $locale->language);
        $this->assertSame('US', $locale->region);
        $this->assertNull($locale->variant);

        $locale = new Locale('ru');
        $this->assertSame('ru', $locale->language);
        $this->assertNull($locale->region);
        $this->assertNull($locale->variant);

        $locale = new Locale('zh_CN_Hans');
        $this->assertSame('zh', $locale->language);
        $this->assertSame('CN', $locale->region);
        $this->assertSame('Hans', $locale->variant);
    }

    #[Test]
    public function invalidLocaleThrowsException(): void
    {
        $this->expectException(InvalidLocaleException::class);
        new Locale('invalid');
    }

    #[Test]
    public function toStringReturnsCorrectFormat(): void
    {
        $locale = new Locale('en_US');
        $this->assertSame('en_US', $locale->toString());

        $locale = new Locale('ru');
        $this->assertSame('ru', $locale->toString());

        $locale = new Locale('zh_CN_Hans');
        $this->assertSame('zh_CN_Hans', $locale->toString());
    }

    #[Test]
    public function getFallbacksReturnsCorrectFallbacks(): void
    {
        $locale = new Locale('en_US');
        $this->assertSame(['en'], $locale->getFallbacks());

        $locale = new Locale('ru');
        $this->assertSame([], $locale->getFallbacks());

        $locale = new Locale('zh_CN_Hans');
        $this->assertSame(['zh_CN', 'zh'], $locale->getFallbacks());
    }
}

