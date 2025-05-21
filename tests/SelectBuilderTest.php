<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Generator\NestedMessageBuilder;
use Bermuda\Polyglot\Generator\SelectBuilder;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SelectBuilder::class)]
#[CoversClass(NestedMessageBuilder::class)]
final class SelectBuilderTest extends TestCase
{
    private PluralRuleProviderInterface $pluralRuleProvider;

    protected function setUp(): void
    {
        // Используем реальный CldrPluralRuleProvider
        $this->pluralRuleProvider = new CldrPluralRuleProvider();
    }

    /**
     * @return array<string, array{string, array<string, string>, string}>
     */
    public static function selectCasesProvider(): array
    {
        return [
            'basic select' => [
                'status',
                [
                    'success' => 'Operation successful',
                    'error' => 'Operation failed',
                    'pending' => 'Operation in progress'
                ],
                '{status, select, success{Operation successful} error{Operation failed} pending{Operation in progress}}'
            ],
            'gender select' => [
                'gender',
                [
                    'male' => 'He',
                    'female' => 'She',
                    'other' => 'They'
                ],
                '{gender, select, male{He} female{She} other{They}}'
            ],
            'select with special chars' => [
                'option',
                [
                    'a' => 'Option {A}',
                    'b' => 'Option {B} with #',
                    'c' => 'Option {C} with {placeholder}'
                ],
                '{option, select, a{Option {A}} b{Option {B} with #} c{Option {C} with {placeholder}}}'
            ]
        ];
    }

    #[DataProvider('selectCasesProvider')]
    public function testBuildWithCases(string $variable, array $cases, string $expected): void
    {
        $builder = new SelectBuilder($variable);

        foreach ($cases as $value => $text) {
            $builder->when($value, $text);
        }

        $result = $builder->build();
        self::assertSame($expected, $result);
    }

    public function testOtherwise(): void
    {
        $builder = new SelectBuilder('type');
        $builder->when('pdf', 'PDF Document');
        $builder->when('doc', 'Word Document');
        $builder->otherwise('Unknown Document Type');

        $result = $builder->build();

        self::assertSame(
            '{type, select, pdf{PDF Document} doc{Word Document} other{Unknown Document Type}}',
            $result
        );
    }

    public function testOtherwiseOverridesExistingOther(): void
    {
        $builder = new SelectBuilder('type');
        $builder->when('pdf', 'PDF Document');
        $builder->when('other', 'Other Document Type');
        $builder->otherwise('Unknown Document Type');

        $result = $builder->build();

        self::assertSame(
            '{type, select, pdf{PDF Document} other{Unknown Document Type}}',
            $result
        );
    }

    public function testWhenWithCallableUsingPluralBuilder(): void
    {
        $builder = new SelectBuilder('gender');

        $builder->when('male', function($nestedBuilder) {
            self::assertInstanceOf(NestedMessageBuilder::class, $nestedBuilder);
            return $nestedBuilder->plural('count', 'en', $this->pluralRuleProvider)
                ->when('one', 'He has # item')
                ->when('other', 'He has # items');
        });

        $builder->when('female', function($nestedBuilder) {
            return $nestedBuilder->plural('count', 'en', $this->pluralRuleProvider)
                ->when('one', 'She has # item')
                ->when('other', 'She has # items');
        });

        $result = $builder->build();

        $expected = '{gender, select, male{{count, plural, one{He has # item} other{He has # items}}} '
            . 'female{{count, plural, one{She has # item} other{She has # items}}}}';

        self::assertSame($expected, $result);
    }

    public function testWhenWithCallableUsingPluralBuilderForRussian(): void
    {
        $builder = new SelectBuilder('gender');

        $builder->when('male', function($nestedBuilder) {
            return $nestedBuilder->plural('count', 'ru', $this->pluralRuleProvider)
                ->when('one', 'Он купил # товар')
                ->when('few', 'Он купил # товара')
                ->when('many', 'Он купил # товаров');
        });

        $builder->when('female', function($nestedBuilder) {
            return $nestedBuilder->plural('count', 'ru', $this->pluralRuleProvider)
                ->when('one', 'Она купила # товар')
                ->when('few', 'Она купила # товара')
                ->when('many', 'Она купила # товаров');
        });

        $result = $builder->build();

        // Проверяем, что результат содержит правильные категории для русского языка
        self::assertStringContainsString('one{Он купил # товар}', $result);
        self::assertStringContainsString('few{Он купил # товара}', $result);
        self::assertStringContainsString('many{Он купил # товаров}', $result);
        self::assertStringContainsString('one{Она купила # товар}', $result);
        self::assertStringContainsString('few{Она купила # товара}', $result);
        self::assertStringContainsString('many{Она купила # товаров}', $result);
    }

    public function testWhenWithCallableUsingAnotherSelect(): void
    {
        $builder = new SelectBuilder('device');

        $builder->when('mobile', function($nestedBuilder) {
            return $nestedBuilder->select('os')
                ->when('ios', 'iPhone')
                ->when('android', 'Android Phone')
                ->otherwise('Unknown Mobile');
        });

        $builder->when('desktop', function($nestedBuilder) {
            return $nestedBuilder->select('os')
                ->when('windows', 'Windows PC')
                ->when('mac', 'Mac')
                ->when('linux', 'Linux PC')
                ->otherwise('Unknown Desktop');
        });

        $result = $builder->build();

        $expected = '{device, select, mobile{{os, select, ios{iPhone} android{Android Phone} '
            . 'other{Unknown Mobile}}} desktop{{os, select, windows{Windows PC} mac{Mac} '
            . 'linux{Linux PC} other{Unknown Desktop}}}}';

        self::assertSame($expected, $result);
    }

    public function testDeepNestedStructures(): void
    {
        $builder = new SelectBuilder('userType');

        $builder->when('admin', function($n1) {
            return $n1->select('device')
                ->when('mobile', function($n2) {
                    return $n2->select('os')
                        ->when('ios', 'Admin on iOS mobile')
                        ->when('android', 'Admin on Android mobile')
                        ->otherwise('Admin on unknown mobile');
                })
                ->when('desktop', function($n2) {
                    return $n2->select('os')
                        ->when('windows', 'Admin on Windows desktop')
                        ->when('mac', 'Admin on Mac desktop')
                        ->otherwise('Admin on unknown desktop');
                })
                ->otherwise('Admin on unknown device');
        });

        $builder->when('user', 'Regular user');

        $result = $builder->build();

        // Проверяем наличие всех ожидаемых компонентов в результате
        self::assertStringContainsString('userType, select,', $result);
        self::assertStringContainsString('admin{', $result);
        self::assertStringContainsString('device, select,', $result);
        self::assertStringContainsString('mobile{', $result);
        self::assertStringContainsString('os, select,', $result);
        self::assertStringContainsString('ios{Admin on iOS mobile}', $result);
        self::assertStringContainsString('user{Regular user}', $result);

        // Проверяем, что все ожидаемые вложенные элементы присутствуют
        self::assertStringContainsString('Admin on Windows desktop', $result);
        self::assertStringContainsString('Admin on Mac desktop', $result);
        self::assertStringContainsString('Admin on unknown desktop', $result);
        self::assertStringContainsString('Admin on Android mobile', $result);
        self::assertStringContainsString('Admin on unknown mobile', $result);
        self::assertStringContainsString('Admin on unknown device', $result);
    }

    public function testCallableReturningString(): void
    {
        $builder = new SelectBuilder('type');

        $builder->when('special', function($nestedBuilder) {
            // Возвращаем строку вместо использования билдера
            return 'Special value with {placeholder}';
        });

        $builder->when('normal', 'Normal value');

        $result = $builder->build();

        self::assertSame(
            '{type, select, special{Special value with {placeholder}} normal{Normal value}}',
            $result
        );
    }
}