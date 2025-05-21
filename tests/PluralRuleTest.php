<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Exception\RuleNotFoundException;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use Bermuda\Polyglot\PluralRule\PluralCategory;
use Bermuda\Polyglot\PluralRule\PluralRule;
use Bermuda\Polyglot\PluralRule\PluralRuleProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PluralRule::class)]
#[CoversClass(CldrPluralRuleProvider::class)]
#[CoversClass(PluralCategory::class)]
final class PluralRuleTest extends TestCase
{
    private PluralRuleProviderInterface $provider;

    protected function setUp(): void
    {
        $this->provider = new CldrPluralRuleProvider();
    }

    /**
     * @return array<string, array{string, array<int, PluralCategory>}>
     */
    public static function supportedLanguagesProvider(): array
    {
        return [
            'english' => ['en', [
                0 => PluralCategory::OTHER,
                1 => PluralCategory::ONE,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'chinese' => ['zh', [
                0 => PluralCategory::OTHER,
                1 => PluralCategory::OTHER,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'spanish' => ['es', [
                0 => PluralCategory::OTHER,
                1 => PluralCategory::ONE,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'arabic' => ['ar', [
                0 => PluralCategory::ZERO,
                1 => PluralCategory::ONE,
                2 => PluralCategory::TWO,
                3 => PluralCategory::FEW,
                9 => PluralCategory::FEW,
                11 => PluralCategory::MANY,
                99 => PluralCategory::MANY,
                100 => PluralCategory::OTHER,
                102 => PluralCategory::OTHER,
            ]],
            'portuguese' => ['pt', [
                0 => PluralCategory::OTHER,
                1 => PluralCategory::ONE,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'indonesian' => ['id', [
                0 => PluralCategory::OTHER,
                1 => PluralCategory::OTHER,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'french' => ['fr', [
                0 => PluralCategory::ONE,
                1 => PluralCategory::ONE,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'japanese' => ['ja', [
                0 => PluralCategory::OTHER,
                1 => PluralCategory::OTHER,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'russian' => ['ru', [
                0 => PluralCategory::MANY,
                1 => PluralCategory::ONE,
                2 => PluralCategory::FEW,
                3 => PluralCategory::FEW,
                4 => PluralCategory::FEW,
                5 => PluralCategory::MANY,
                11 => PluralCategory::MANY,
                20 => PluralCategory::MANY,
                21 => PluralCategory::ONE,
                22 => PluralCategory::FEW,
                24 => PluralCategory::FEW,
                101 => PluralCategory::ONE,
            ]],
            'german' => ['de', [
                0 => PluralCategory::OTHER,
                1 => PluralCategory::ONE,
                2 => PluralCategory::OTHER,
                5 => PluralCategory::OTHER,
                11 => PluralCategory::OTHER,
                100 => PluralCategory::OTHER,
            ]],
            'polish' => ['pl', [
                0 => PluralCategory::MANY,
                1 => PluralCategory::ONE,
                2 => PluralCategory::FEW,
                3 => PluralCategory::FEW,
                4 => PluralCategory::FEW,
                5 => PluralCategory::MANY,
                11 => PluralCategory::MANY,
                12 => PluralCategory::MANY,
                21 => PluralCategory::MANY,
                22 => PluralCategory::FEW,
                24 => PluralCategory::FEW,
                101 => PluralCategory::MANY,
                102 => PluralCategory::FEW,
                104 => PluralCategory::FEW,
                122 => PluralCategory::FEW,
            ]],
        ];
    }

    /**
     * @throws RuleNotFoundException
     */
    #[DataProvider('supportedLanguagesProvider')]
    public function testSupportedLanguagePluralRules(string $locale, array $testCases): void
    {
        $rule = $this->provider->getRule($locale);

        foreach ($testCases as $number => $expectedCategory) {
            self::assertSame(
                $expectedCategory,
                $rule->getCategory($number),
                "Failed asserting that number $number for locale $locale has correct plural category"
            );
        }
    }

    public function testUnknownLocaleThrowsException(): void
    {
        $this->expectException(RuleNotFoundException::class);
        $this->expectExceptionMessage('No rule found for xx');
        $this->provider->getRule('xx');
    }

    /**
     * @return array<string, array{int, PluralCategory}>
     */
    public static function customPluralRuleDataProvider(): array
    {
        return [
            'zero category for 0' => [0, PluralCategory::ZERO],
            'one category for 1' => [1, PluralCategory::ONE],
            'other category for 2' => [2, PluralCategory::OTHER],
            'other category for 10' => [10, PluralCategory::OTHER],
        ];
    }

    #[DataProvider('customPluralRuleDataProvider')]
    public function testCustomPluralRule(int $number, PluralCategory $expected): void
    {
        $rule = new PluralRule('custom', 3, function (int $n): PluralCategory {
            if ($n === 0) {
                return PluralCategory::ZERO;
            } elseif ($n === 1) {
                return PluralCategory::ONE;
            } else {
                return PluralCategory::OTHER;
            }
        });

        self::assertSame($expected, $rule->getCategory($number));
    }
}