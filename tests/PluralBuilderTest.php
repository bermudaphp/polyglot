<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Generator\PluralBuilder;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;
use Bermuda\Polyglot\PluralRule\PluralMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PluralBuilder::class)]
#[CoversClass(PluralMap::class)]
final class PluralBuilderTest extends TestCase
{
    private PluralRuleProviderInterface $pluralRuleProvider;

    protected function setUp(): void
    {
        $this->pluralRuleProvider = new CldrPluralRuleProvider();
    }

    /**
     * @return array<string, array{string, array<string, string>, string, string}>
     */
    public static function pluralCasesProvider(): array
    {
        return [
            'english simple' => [
                'en',
                ['one' => '# item', 'other' => '# items'],
                'count',
                '{count, plural, one{# item} other{# items}}'
            ],
            'russian multiple cases' => [
                'ru',
                [
                    'one' => '# товар',
                    'few' => '# товара',
                    'many' => '# товаров'
                ],
                'amount',
                '{amount, plural, one{# товар} few{# товара} many{# товаров}}'
            ],
            'arabic all cases' => [
                'ar',
                [
                    'zero' => 'لا عناصر',
                    'one' => 'عنصر واحد',
                    'two' => 'عنصران',
                    'few' => '# عناصر',
                    'many' => '# عنصرًا',
                    'other' => '# عنصر'
                ],
                'n',
                '{n, plural, zero{لا عناصر} one{عنصر واحد} two{عنصران} ' .
                'few{# عناصر} many{# عنصرًا} other{# عنصر}}'
            ],
            'partial cases' => [
                'ru',
                ['one' => '# элемент', 'many' => '# элементов'],
                'num',
                '{num, plural, one{# элемент} many{# элементов}}'
            ]
        ];
    }

    #[DataProvider('pluralCasesProvider')]
    public function testBuildWithCases(string $locale, array $cases, string $variable, string $expected): void
    {
        $builder = new PluralBuilder($variable, $locale, $this->pluralRuleProvider);

        foreach ($cases as $category => $text) {
            $builder->when($category, $text);
        }

        $result = $builder->build();
        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string, string, array<string, string>}>
     */
    public static function withInflectionsProvider(): array
    {
        return [
            'english defaults' => [
                'en',
                'items',
                '# item',
                [
                    'one' => '',
                    'other' => 's'
                ]
            ],
            'russian defaults' => [
                'ru',
                'count',
                '# элемент',
                [
                    'one' => '',
                    'few' => 'а',
                    'many' => 'ов'
                ]
            ],
            'arabic defaults' => [
                'ar',
                'n',
                '# item',
                [
                    'one' => '',
                    'two' => 's',
                    'few' => 's',
                    'many' => 's',
                    'other' => 's'
                ]
            ]
        ];
    }

    #[DataProvider('withInflectionsProvider')]
    public function testWithInflections(
        string $locale,
        string $variable,
        string $baseMessage,
        array $endings
    ): void {
        $builder = new PluralBuilder($variable, $locale, $this->pluralRuleProvider);
        $builder->withInflections($baseMessage, $endings);

        $result = $builder->build();

        // Проверяем наличие ожидаемых категорий в результате
        // в зависимости от языка
        switch($locale) {
            case 'en':
                self::assertStringContainsString('one{', $result);
                self::assertStringContainsString('other{', $result);
                break;
            case 'ru':
                self::assertStringContainsString('one{', $result);
                self::assertStringContainsString('few{', $result);
                self::assertStringContainsString('many{', $result);
                break;
            case 'ar':
                self::assertStringContainsString('zero{', $result);
                self::assertStringContainsString('one{', $result);
                self::assertStringContainsString('two{', $result);
                self::assertStringContainsString('few{', $result);
                self::assertStringContainsString('many{', $result);
                break;
        }
    }

    public function testWhenAfterWithInflections(): void
    {
        $builder = new PluralBuilder('count', 'en', $this->pluralRuleProvider);
        $builder->withInflections('# generic item', ['one' => '', 'other' => 's']);
        $builder->when('one', 'Exactly one custom item');

        $result = $builder->build();

        self::assertStringContainsString('one{Exactly one custom item}', $result);
        self::assertStringContainsString('other{', $result);
    }

    public function testWithInflectionsAfterWhen(): void
    {
        $builder = new PluralBuilder('count', 'ru', $this->pluralRuleProvider);
        $builder->when('one', '# специальный элемент');
        $builder->withInflections('# обычный элемент', [
            'one' => '',
            'few' => 'а',
            'many' => 'ов'
        ]);

        $result = $builder->build();

        self::assertStringContainsString('one{# специальный элемент}', $result);
        self::assertStringContainsString('few{', $result);
        self::assertStringContainsString('many{', $result);
    }

    public function testHandlesExceptionInWithInflections(): void
    {
        // Создаем PluralRuleProvider, который выбрасывает исключения
        $brokenProvider = $this->createMock(PluralRuleProviderInterface::class);
        $brokenProvider->method('getRule')->willThrowException(new \Exception('Failed to get rule'));

        $builder = new PluralBuilder('count', 'en', $brokenProvider);
        $builder->withInflections('# item', ['other' => 's']);

        $result = $builder->build();

        // Должна быть добавлена категория 'other' для восстановления после ошибки
        self::assertStringContainsString('other{', $result);
    }

    public function testOtherwiseWhenOtherIsMissing(): void
    {
        $builder = new PluralBuilder('count', 'en', $this->pluralRuleProvider);
        $builder->when('one', 'One item');
        $builder->otherwise('Multiple items');

        $result = $builder->build();

        self::assertSame('{count, plural, one{One item} other{Multiple items}}', $result);
    }

    public function testOtherwiseOverridesExistingOther(): void
    {
        $builder = new PluralBuilder('count', 'en', $this->pluralRuleProvider);
        $builder->when('one', 'One item');
        $builder->when('other', 'Some items');
        $builder->otherwise('Many items');

        $result = $builder->build();

        self::assertSame('{count, plural, one{One item} other{Many items}}', $result);
    }

    /**
     * Проверка интеграции с PluralMap
     */
    public function testPluralMapIntegration(): void
    {
        $languages = array_merge(
            PluralMap::SLAVIC_LANGUAGES,
            PluralMap::WESTERN_EUROPEAN,
            PluralMap::SEMITIC_LANGUAGES,
            PluralMap::NO_PLURAL_LANGUAGES
        );

        $sampleLanguages = array_slice($languages, 0, 5);

        foreach ($sampleLanguages as $language) {
            $examples = PluralMap::getCanonicalExamples($language);
            self::assertIsArray($examples);
            self::assertNotEmpty($examples);

            foreach ($examples as $example) {
                self::assertIsInt($example);
            }
        }
    }
}