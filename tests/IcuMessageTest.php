<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Generator\IcuMessage;
use Bermuda\Polyglot\Generator\IcuMessageBuilder;
use Bermuda\Polyglot\Generator\PluralBuilder;
use Bermuda\Polyglot\Generator\SelectBuilder;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IcuMessage::class)]
final class IcuMessageTest extends TestCase
{
    private CldrPluralRuleProvider $pluralRuleProvider;

    protected function setUp(): void
    {
        $this->pluralRuleProvider = new CldrPluralRuleProvider();
    }

    /**
     * Test the for() method to create a message builder for a specific locale
     */
    public function testFor(): void
    {
        $builder = IcuMessage::for('en', $this->pluralRuleProvider);

        $this->assertInstanceOf(IcuMessageBuilder::class, $builder);

        // Test that the returned builder can create a simple message
        $message = $builder->message('Hello, {name}!');
        $this->assertEquals('Hello, {name}!', $message);
    }

    /**
     * Test the plural() method to create a plural builder
     */
    public function testPlural(): void
    {
        $builder = IcuMessage::plural('count', 'en', $this->pluralRuleProvider);

        $this->assertInstanceOf(PluralBuilder::class, $builder);

        // Test that the builder creates correct ICU format for English
        $message = $builder
            ->when('one', 'You have # item')
            ->when('other', 'You have # items')
            ->build();

        $this->assertEquals('{count, plural, one{You have # item} other{You have # items}}', $message);

        // Test that it works with different locales
        $russianBuilder = IcuMessage::plural('count', 'ru', $this->pluralRuleProvider);
        $russianMessage = $russianBuilder
            ->when('one', '# товар')
            ->when('few', '# товара')
            ->when('many', '# товаров')
            ->when('other', '# товаров')
            ->build();

        $this->assertEquals('{count, plural, one{# товар} few{# товара} many{# товаров} other{# товаров}}', $russianMessage);
    }

    /**
     * Test the select() method to create a select builder
     */
    public function testSelect(): void
    {
        $builder = IcuMessage::select('gender');

        $this->assertInstanceOf(SelectBuilder::class, $builder);

        // Test that the builder creates correct ICU format
        $message = $builder
            ->when('male', 'He')
            ->when('female', 'She')
            ->otherwise('They')
            ->build();

        $this->assertEquals('{gender, select, male{He} female{She} other{They}}', $message);
    }

    /**
     * Test the gender() convenience method
     */
    public function testGender(): void
    {
        $message = IcuMessage::gender('gender', 'He will attend', 'She will attend', 'They will attend');

        $this->assertEquals(
            '{gender, select, male{He will attend} female{She will attend} other{They will attend}}',
            $message
        );
    }

    /**
     * Provider for date format styles
     */
    public static function dateStylesProvider(): array
    {
        return [
            'short' => ['short', '{date, date, short}'],
            'medium' => ['medium', '{date, date, medium}'],
            'long' => ['long', '{date, date, long}'],
            'full' => ['full', '{date, date, full}'],
        ];
    }

    /**
     * Test the date() method with different styles
     */
    #[DataProvider('dateStylesProvider')]
    public function testDate(string $style, string $expected): void
    {
        $message = IcuMessage::date('date', $style);
        $this->assertEquals($expected, $message);
    }

    /**
     * Provider for time format styles
     */
    public static function timeStylesProvider(): array
    {
        return [
            'short' => ['short', '{time, time, short}'],
            'medium' => ['medium', '{time, time, medium}'],
            'long' => ['long', '{time, time, long}'],
            'full' => ['full', '{time, time, full}'],
        ];
    }

    /**
     * Test the time() method with different styles
     */
    #[DataProvider('timeStylesProvider')]
    public function testTime(string $style, string $expected): void
    {
        $message = IcuMessage::time('time', $style);
        $this->assertEquals($expected, $message);
    }

    /**
     * Provider for number format styles
     */
    public static function numberStylesProvider(): array
    {
        return [
            'default' => [null, null, '{number, number}'],
            'currency' => ['currency', null, '{number, number, currency}'],
            'percent' => ['percent', null, '{number, number, percent}'],
            'scientific' => ['scientific', null, '{number, number, scientific}'],
            'with options' => ['currency', 'USD', '{number, number, currency, USD}'],
        ];
    }

    /**
     * Test the number() method with different styles
     */
    #[DataProvider('numberStylesProvider')]
    public function testNumber(?string $style, ?string $options, string $expected): void
    {
        $message = IcuMessage::number('number', $style, $options);
        $this->assertEquals($expected, $message);
    }

    /**
     * Test the withInflections method in PluralBuilder
     */
    public function testPluralWithInflections(): void
    {
        // Test for English
        $builder = IcuMessage::plural('count', 'en', $this->pluralRuleProvider);
        $message = $builder->withInflections('# item', [
            'one' => '',
            'other' => 's'
        ])->build();

        $this->assertStringContainsString('one{1 item}', $message);
        $this->assertStringContainsString('other{', $message);

        // Test for Russian
        $builder = IcuMessage::plural('count', 'ru', $this->pluralRuleProvider);
        $message = $builder->withInflections('# товар', [
            'one' => '',
            'few' => 'а',
            'many' => 'ов',
            'other' => 'ов'
        ])->build();

        $this->assertStringContainsString('one{', $message);
        $this->assertStringContainsString('few{', $message);
        $this->assertStringContainsString('many{', $message);
        $this->assertStringContainsString('other{', $message);
    }

    /**
     * Test nested formatting with select and plural
     */
    public function testNestedFormatting(): void
    {
        $builder = IcuMessage::select('gender');
        $message = $builder
            ->when('male', function ($b) {
                return $b->plural('count', 'en', $this->pluralRuleProvider)
                    ->when('one', 'He has # apple')
                    ->when('other', 'He has # apples');
            })
            ->when('female', function ($b) {
                return $b->plural('count', 'en', $this->pluralRuleProvider)
                    ->when('one', 'She has # apple')
                    ->when('other', 'She has # apples');
            })
            ->build();

        $this->assertEquals(
            '{gender, select, male{{count, plural, one{He has # apple} other{He has # apples}}} ' .
            'female{{count, plural, one{She has # apple} other{She has # apples}}}}',
            $message
        );
    }
}
