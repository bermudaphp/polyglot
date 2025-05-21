<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Formatter\IcuMessageFormatter;
use Bermuda\Polyglot\PluralRule\CldrPluralRuleProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IcuMessageFormatter::class)]
class IcuMessageFormatterTest extends TestCase
{
    private IcuMessageFormatter $formatter;

    protected function setUp(): void
    {
        $pluralRuleProvider = new CldrPluralRuleProvider();
        $this->formatter = new IcuMessageFormatter($pluralRuleProvider);
    }

    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function simpleMessageProvider(): array
    {
        return [
            'simple message' => [
                'Hello, {name}!',
                ['name' => 'John'],
                'Hello, John!'
            ],
            'multiple parameters' => [
                'Hello, {name}! Welcome to {site}.',
                ['name' => 'John', 'site' => 'Example.com'],
                'Hello, John! Welcome to Example.com.'
            ],
            'numeric parameters' => [
                'You have {count} items.',
                ['count' => 5],
                'You have 5 items.'
            ],
            'missing parameter keeps placeholder' => [
                'Hello, {name}!',
                [],
                'Hello, {name}!'
            ],
            'repeated parameter' => [
                '{name} likes {name}\'s profile picture.',
                ['name' => 'Alice'],
                'Alice likes Alice\'s profile picture.'
            ]
        ];
    }

    #[DataProvider('simpleMessageProvider')]
    public function testSimpleParameterSubstitution(string $message, array $parameters, string $expected): void
    {
        $result = $this->formatter->format($message, $parameters);
        $this->assertEquals($expected, $result);
    }

    public function testHandlesMissingParameters(): void
    {
        $message = '{gender, select, male{He is} female{She is} other{They are}} a programmer.';
        $result = $this->formatter->format($message, []);
        
        // For intl extension, it should preserve the original message format
        // For the fallback implementation, it might simplify to '{gender} a programmer.'
        
        // Check either the complete format is preserved or at least gender placeholder remains
        $this->assertContains($result, [
            $message, // Original preserved (with intl extension)
            '{gender, select, male{He is} female{She is} other{They are}} a programmer.', // Original format
            '{gender} a programmer.' // Fallback implementation
        ]);
    }

    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function pluralMessageProvider(): array
    {
        return [
            'english one' => [
                '{count, plural, one{# item} other{# items}}',
                ['count' => 1, '_locale' => 'en'],
                '1 item'
            ],
            'english other' => [
                '{count, plural, one{# item} other{# items}}',
                ['count' => 5, '_locale' => 'en'],
                '5 items'
            ],
            'russian one' => [
                '{count, plural, one{# товар} few{# товара} many{# товаров} other{# товаров}}',
                ['count' => 1, '_locale' => 'ru'],
                '1 товар'
            ],
            'russian few' => [
                '{count, plural, one{# товар} few{# товара} many{# товаров} other{# товаров}}',
                ['count' => 3, '_locale' => 'ru'],
                '3 товара'
            ],
            'russian many' => [
                '{count, plural, one{# товар} few{# товара} many{# товаров} other{# товаров}}',
                ['count' => 5, '_locale' => 'ru'],
                '5 товаров'
            ]
        ];
    }

    #[DataProvider('pluralMessageProvider')]
    public function testPluralFormatting(string $message, array $parameters, string $expected): void
    {
        // Skip test if intl extension is not loaded
        if (!extension_loaded('intl') || !class_exists('MessageFormatter')) {
            $this->markTestSkipped('Intl extension not loaded');
        }
        
        $result = $this->formatter->format($message, $parameters);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{string, array<string, mixed>, string}>
     */
    public static function selectMessageProvider(): array
    {
        return [
            'male' => [
                '{gender, select, male{He} female{She} other{They}}',
                ['gender' => 'male'],
                'He'
            ],
            'female' => [
                '{gender, select, male{He} female{She} other{They}}',
                ['gender' => 'female'],
                'She'
            ],
            'other' => [
                '{gender, select, male{He} female{She} other{They}}',
                ['gender' => 'other'],
                'They'
            ],
            'unknown falls back to other' => [
                '{gender, select, male{He} female{She} other{They}}',
                ['gender' => 'unknown'],
                'They'
            ]
        ];
    }

    #[DataProvider('selectMessageProvider')]
    public function testSelectFormatting(string $message, array $parameters, string $expected): void
    {
        // Skip test if intl extension is not loaded
        if (!extension_loaded('intl') || !class_exists('MessageFormatter')) {
            $this->markTestSkipped('Intl extension not loaded');
        }
        
        $result = $this->formatter->format($message, $parameters);
        $this->assertEquals($expected, $result);
    }
}
