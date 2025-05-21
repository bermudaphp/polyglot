<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Generator\IcuMessage;
use Bermuda\Polyglot\Generator\IcuMessageBuilder;
use Bermuda\Polyglot\Generator\PluralBuilder;
use Bermuda\Polyglot\Generator\SelectBuilder;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IcuMessage::class)]
#[CoversClass(IcuMessageBuilder::class)]
#[CoversClass(PluralBuilder::class)]
#[CoversClass(SelectBuilder::class)]
final class IcuMessageTest extends TestCase
{
    private PluralRuleProviderInterface $pluralRuleProvider;

    protected function setUp(): void
    {
        $this->pluralRuleProvider = new CldrPluralRuleProvider();
    }

    public function testFor(): void
    {
        $builder = IcuMessage::for('en', $this->pluralRuleProvider);
        self::assertInstanceOf(IcuMessageBuilder::class, $builder);
    }

    public function testPlural(): void
    {
        $builder = IcuMessage::plural('count', 'en', $this->pluralRuleProvider);
        self::assertInstanceOf(PluralBuilder::class, $builder);
    }

    public function testSelect(): void
    {
        $builder = IcuMessage::select('gender');
        self::assertInstanceOf(SelectBuilder::class, $builder);
    }

    public function testGender(): void
    {
        $template = IcuMessage::gender('gender', 'male text', 'female text', 'other text');
        self::assertSame(
            '{gender, select, male{male text} female{female text} other{other text}}',
            $template
        );
    }

    public function testDate(): void
    {
        $template = IcuMessage::date('orderDate', 'short');
        self::assertSame('{orderDate, date, short}', $template);

        $template = IcuMessage::date('orderDate');
        self::assertSame('{orderDate, date, medium}', $template);
    }

    public function testTime(): void
    {
        $template = IcuMessage::time('orderTime', 'short');
        self::assertSame('{orderTime, time, short}', $template);

        $template = IcuMessage::time('orderTime');
        self::assertSame('{orderTime, time, medium}', $template);
    }

    public function testNumber(): void
    {
        $template = IcuMessage::number('amount');
        self::assertSame('{amount, number}', $template);

        $template = IcuMessage::number('amount', 'currency');
        self::assertSame('{amount, number, currency}', $template);

        $template = IcuMessage::number('amount', 'currency', 'EUR');
        self::assertSame('{amount, number, currency, EUR}', $template);
    }

    public function testPluralBuilder(): void
    {
        $template = IcuMessage::plural('count', 'en', $this->pluralRuleProvider)
            ->when('one', '# item')
            ->when('other', '# items')
            ->build();

        self::assertSame('{count, plural, one{# item} other{# items}}', $template);
    }

    public function testPluralBuilderWithOtherwise(): void
    {
        $template = IcuMessage::plural('count', 'en', $this->pluralRuleProvider)
            ->when('one', '# item')
            ->otherwise('# items')
            ->build();

        self::assertSame('{count, plural, one{# item} other{# items}}', $template);
    }

    public function testSelectBuilder(): void
    {
        $template = IcuMessage::select('type')
            ->when('success', 'Operation completed successfully')
            ->when('error', 'An error occurred')
            ->otherwise('Unknown status')
            ->build();

        self::assertSame(
            '{type, select, success{Operation completed successfully} error{An error occurred} other{Unknown status}}',
            $template
        );
    }

    public function testWithDefaults(): void
    {
        $template = IcuMessage::plural('count', 'ru', $this->pluralRuleProvider)
            ->withDefaults(fn($count) => "$count товар(а/ов)")
            ->build();

        // Should contain all three Russian plural forms
        self::assertStringContainsString('one{1 товар(а/ов)}', $template);
        self::assertStringContainsString('few{2 товар(а/ов)}', $template);
        self::assertStringContainsString('many{5 товар(а/ов)}', $template);
    }

    public function testWithDefaultsWithCustomCases(): void
    {
        $template = IcuMessage::plural('count', 'ru', $this->pluralRuleProvider)
            ->when('one', '# персональный товар')
            ->withDefaults(fn($count) => "$count стандартных товар(а/ов)")
            ->build();

        // Should preserve custom cases and add missing ones
        self::assertStringContainsString('one{# персональный товар}', $template);
        self::assertStringContainsString('few{2 стандартных товар(а/ов)}', $template);
        self::assertStringContainsString('many{5 стандартных товар(а/ов)}', $template);
    }

    public function testNestedStructures(): void
    {
        $template = IcuMessage::select('gender')
            ->when('male', function($builder) {
                return $builder->plural('count', 'en', $this->pluralRuleProvider)
                    ->when('one', 'He has # item')
                    ->when('other', 'He has # items');
            })
            ->when('female', function($builder) {
                return $builder->plural('count', 'en', $this->pluralRuleProvider)
                    ->when('one', 'She has # item')
                    ->when('other', 'She has # items');
            })
            ->otherwise('They have items')
            ->build();

        $expected = '{gender, select, ' .
            'male{{count, plural, one{He has # item} other{He has # items}}} ' .
            'female{{count, plural, one{She has # item} other{She has # items}}} ' .
            'other{They have items}}';

        self::assertSame($expected, $template);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function messageProvider(): array
    {
        return [
            'simple message' => [
                '{greeting} {name}',
                '{greeting} {name}'
            ],
            'message with nested expression' => [
                'Hello, {name}! You have {count, plural, one{# message} other{# messages}}.',
                'Hello, {name}! You have {count, plural, one{# message} other{# messages}}.'
            ]
        ];
    }

    #[DataProvider('messageProvider')]
    public function testMessage(string $message, string $expected): void
    {
        $result = IcuMessage::for('en', $this->pluralRuleProvider)->message($message);
        self::assertSame($expected, $result);
    }
}