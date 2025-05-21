<?php

declare(strict_types=1);

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Loader\JsonFileMessageLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonFileMessageLoader::class)]
class JsonFileMessageLoaderTest extends TestCase
{
    private string $testDir;
    private JsonFileMessageLoader $loader;

    protected function setUp(): void
    {
        // Create a temporary directory for test files
        $this->testDir = sys_get_temp_dir() . '/polyglot_tests_' . uniqid();
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0777, true);
        }

        // Create test locale directory
        mkdir($this->testDir . '/en', 0777, true);

        // Create test file with valid JSON content
        $validJson = json_encode([
            'welcome' => 'Welcome!',
            'goodbye' => 'Goodbye!'
        ]);
        file_put_contents($this->testDir . '/en/messages.json', $validJson);

        // Create test file with invalid JSON content
        file_put_contents($this->testDir . '/en/invalid.json', '{ "broken: "json" }');

        // Create loader instance
        $this->loader = new JsonFileMessageLoader($this->testDir);
    }

    protected function tearDown(): void
    {
        // Clean up created files and directories
        if (file_exists($this->testDir . '/en/messages.json')) {
            unlink($this->testDir . '/en/messages.json');
        }
        if (file_exists($this->testDir . '/en/invalid.json')) {
            unlink($this->testDir . '/en/invalid.json');
        }
        if (is_dir($this->testDir . '/en')) {
            rmdir($this->testDir . '/en');
        }
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
    }

    public function testLoadsMessagesCorrectly(): void
    {
        $messages = $this->loader->load('en', 'messages');

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('welcome', $messages);
        $this->assertArrayHasKey('goodbye', $messages);
        $this->assertEquals('Welcome!', $messages['welcome']);
        $this->assertEquals('Goodbye!', $messages['goodbye']);
    }

    public function testExistsReturnsTrueForExistingFile(): void
    {
        $this->assertTrue($this->loader->exists('en', 'messages'));
    }

    public function testExistsReturnsFalseForNonExistingFile(): void
    {
        $this->assertFalse($this->loader->exists('en', 'nonexistent'));
        $this->assertFalse($this->loader->exists('fr', 'messages'));
    }

    public function testThrowsExceptionForInvalidJson(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->loader->load('en', 'invalid');
    }

    public function testReturnsEmptyArrayForNonExistentFile(): void
    {
        $messages = $this->loader->load('en', 'nonexistent');
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }
}
