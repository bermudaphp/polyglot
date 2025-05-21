<?php

namespace Bermuda\Polyglot\Tests;

use Bermuda\Polyglot\Loader\PhpArrayMessageLoader;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpArrayMessageLoader::class)]
class PhpArrayMessageLoaderTest extends TestCase
{
    private vfsStreamDirectory $root;
    private PhpArrayMessageLoader $loader;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root', null, [
            'translations' => [
                'en' => [
                    'messages.php' => '<?php return [
                        "welcome" => "Welcome",
                        "goodbye" => "Goodbye",
                        "nested" => [
                            "key" => "Nested value"
                        ]
                    ];',
                ],
                'fr' => [
                    'messages.php' => '<?php return [
                        "welcome" => "Bienvenue",
                        "goodbye" => "Au revoir"
                    ];',
                ],
                'de' => [
                    'invalid.php' => '<?php return "not an array";',
                ],
            ]
        ]);

        $this->loader = new PhpArrayMessageLoader(vfsStream::url('root/translations'));
    }

    #[Test]
    public function loadsMessagesCorrectly(): void
    {
        $messages = $this->loader->load('en', 'messages');

        $this->assertIsArray($messages);
        $this->assertArrayHasKey('welcome', $messages);
        $this->assertArrayHasKey('goodbye', $messages);
        $this->assertArrayHasKey('nested', $messages);
        $this->assertIsArray($messages['nested']);
        $this->assertArrayHasKey('key', $messages['nested']);

        $this->assertSame('Welcome', $messages['welcome']);
        $this->assertSame('Goodbye', $messages['goodbye']);
        $this->assertSame('Nested value', $messages['nested']['key']);
    }

    #[Test]
    public function existsReturnsTrueForExistingFile(): void
    {
        $this->assertTrue($this->loader->exists('en', 'messages'));
        $this->assertTrue($this->loader->exists('fr', 'messages'));
    }

    #[Test]
    public function existsReturnsFalseForNonExistingFile(): void
    {
        $this->assertFalse($this->loader->exists('en', 'errors'));
        $this->assertFalse($this->loader->exists('ru', 'messages'));
    }

    #[Test]
    public function returnsEmptyArrayForNonExistingFile(): void
    {
        $messages = $this->loader->load('ru', 'messages');
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    #[Test]
    public function throwsExceptionForNonArrayReturn(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->loader->load('de', 'invalid');
    }
}