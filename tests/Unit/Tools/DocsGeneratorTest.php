<?php

namespace Tempcord\Tests\Unit\Tools;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RuntimeException;
use Tempcord\Tools\ApiReflector;
use Tempcord\Tools\DocsGenerator;
use Tempcord\Tools\GuideCompiler;

#[CoversClass(DocsGenerator::class)]
#[CoversClass(ApiReflector::class)]
#[CoversClass(GuideCompiler::class)]
final class DocsGeneratorTest extends BaseTestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * The documentation this replaced described a middleware layer that never
     * existed, because nothing checked it against the code. This is that check:
     * if the committed docs no longer match what the source produces, the build
     * fails rather than the website quietly serving fiction.
     */
    public function test_the_committed_docs_match_the_source(): void
    {
        $stale = [];

        foreach (new DocsGenerator(self::root())->generate() as $path => $expected) {
            $committed = self::root() . '/docs/' . $path;

            if (!is_file($committed)) {
                $stale[] = $path . ' (missing)';
                continue;
            }

            if (file_get_contents($committed) !== $expected) {
                $stale[] = $path . ' (differs)';
            }
        }

        $this->assertSame([], $stale, "Run `composer docs` to bring the documentation back in line.");
    }

    /**
     * Guides carry no code of their own; every example is a real file the suite
     * already compiles, so an example cannot silently stop being true.
     */
    public function test_every_guide_example_comes_from_a_file_that_exists(): void
    {
        $compiler = new GuideCompiler(self::root());
        $missing = [];
        $included = 0;

        foreach (glob(self::root() . '/tools/guides/*.md') ?: [] as $guide) {
            foreach ($compiler->includedPaths((string) file_get_contents($guide)) as $path) {
                $included++;

                if (!is_file(self::root() . '/' . $path)) {
                    $missing[] = basename($guide) . ' includes ' . $path;
                }
            }
        }

        $this->assertSame([], $missing);
        $this->assertGreaterThan(0, $included, 'Guides should show real code rather than describing it');
    }

    public function test_including_a_file_that_does_not_exist_is_an_error(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('which does not exist');

        new GuideCompiler(self::root())->compile('<!-- include: tests/Fixtures/NoSuchCommand.php -->');
    }

    /**
     * Only what a bot author writes. The compiler, definitions and runtime are
     * internal, and documenting them would invite people to depend on them.
     */
    public function test_the_reference_covers_the_public_surface(): void
    {
        $names = [];

        foreach (new ApiReflector()->reflect() as $symbols) {
            foreach ($symbols as $symbol) {
                $names[] = $symbol->name;
            }
        }

        foreach (['Command', 'Subcommand', 'SubcommandGroup', 'Option', 'Event', 'Autocomplete'] as $expected) {
            $this->assertContains($expected, $names);
        }

        $this->assertNotContains('CommandCompiler', $names);
        $this->assertNotContains('CommandDefinition', $names);
    }

    public function test_attribute_targets_are_read_from_the_attribute_itself(): void
    {
        $byName = [];

        foreach (new ApiReflector()->reflect()['attributes'] as $symbol) {
            $byName[$symbol->name] = $symbol;
        }

        $this->assertSame('parameter', $byName['Option']->target);
        $this->assertSame('method', $byName['Subcommand']->target);
        $this->assertSame('class', $byName['SubcommandGroup']->target);
    }

    public function test_parameter_defaults_and_types_are_reported(): void
    {
        $option = null;

        foreach (new ApiReflector()->reflect()['attributes'] as $symbol) {
            if ($symbol->name === 'Option') {
                $option = $symbol;
            }
        }

        $byName = [];

        foreach ($option->parameters as $parameter) {
            $byName[$parameter->name] = $parameter;
        }

        $this->assertTrue($byName['description']->isRequired());
        $this->assertFalse($byName['minLength']->isRequired());
        $this->assertSame('?int', $byName['minLength']->type);
        $this->assertSame('null', $byName['minLength']->default);
    }

    /**
     * A type containing a space, as in array<string, int>, used to defeat the
     * docblock parser and silently drop the description.
     */
    public function test_a_parameter_typed_with_a_space_keeps_its_description(): void
    {
        foreach (new ApiReflector()->reflect()['attributes'] as $symbol) {
            if ($symbol->name !== 'Option') {
                continue;
            }

            foreach ($symbol->parameters as $parameter) {
                if ($parameter->name === 'choices') {
                    $this->assertStringContainsString('the only values Discord will accept', $parameter->summary);

                    return;
                }
            }
        }

        $this->fail('Option should declare a choices parameter');
    }

    public function test_the_json_index_is_valid_and_carries_both_halves(): void
    {
        $files = new DocsGenerator(self::root())->generate();

        $index = json_decode($files['index.json'], true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('guides', $index);
        $this->assertArrayHasKey('reference', $index);
        $this->assertNotEmpty($index['guides']);
        $this->assertSame('Getting started', $index['guides'][0]['title']);
        $this->assertArrayHasKey('attributes', $index['reference']);
    }
}
