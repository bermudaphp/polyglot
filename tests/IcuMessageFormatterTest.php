<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Bermuda\Polyglot\Formatter\IcuMessageFormatter;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;

#[CoversClass(IcuMessageFormatter::class)]
class IcuMessageFormatterTest extends TestCase
{
    private IcuMessageFormatter $formatter;

    protected function setUp(): void
    {
        $pluralRuleProvider = new CldrPluralRuleProvider();
        $this->formatter = new IcuMessageFormatter($pluralRuleProvider);
    }

    #[Test]
    public function formatsSimpleParameters(): void
    {
        $message = 'Hello, {name}!';
        $result = $this->formatter->format($message, ['name' => 'John']);

        $this->assertSame('Hello, John!', $result);
    }

    #[Test]
    public function formatsPlurals(): void
    {
        $message = '{count, plural, one{# item} other{# items}}';

        $result1 = $this->formatter->format($message, ['count' => 1, '_locale' => 'en']);
        $this->assertSame('1 item', $result1);

        $result2 = $this->formatter->format($message, ['count' => 5, '_locale' => 'en']);
        $this->assertSame('5 items', $result2);
    }

    #[Test]
    public function formatsSelectExpressions(): void
    {
        $message = '{gender, select, male{He is} female{She is} other{They are}} a programmer.';

        $result1 = $this->formatter->format($message, ['gender' => 'male']);
        $this->assertSame('He is a programmer.', $result1);

        $result2 = $this->formatter->format($message, ['gender' => 'female']);
        $this->assertSame('She is a programmer.', $result2);

        $result3 = $this->formatter->format($message, ['gender' => 'unknown']);
        $this->assertSame('They are a programmer.', $result3);
    }

    #[Test]
    public function handlesMissingParameters(): void
    {
        $message = '{gender, select, male{He is} female{She is} other{They are}} a programmer.';
        $result = $this->formatter->format($message, []);

        $this->assertSame('{gender, select, male{He is} female{She is} other{They are}} a programmer.', $result);
    }

    #[Test]
    public function handlesNestedExpressions(): void
    {
        $message = '{gender, select, male{He has {count, plural, one{# item} other{# items}}} female{She has {count, plural, one{# item} other{# items}}} other{They have {count, plural, one{# item} other{# items}}}}';

        $result = $this->formatter->format($message, [
            'gender' => 'female',
            'count' => 5,
            '_locale' => 'en'
        ]);

        $this->assertSame('She has 5 items', $result);
    }
}