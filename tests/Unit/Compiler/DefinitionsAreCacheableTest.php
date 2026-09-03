<?php

namespace Tempcord\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\VarExporter\VarExporter;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Definitions\OptionDefinition;
use Tempcord\Tests\Fixtures\InlineGuardedCommand;
use Tempcord\Tests\Fixtures\PlatformCommand;
use Tempcord\Tests\Unit\TestCase;
use Throwable;

/**
 * Discovery is cached by writing it out as PHP source, which means every
 * definition has to be expressible as code that constructs the same thing
 * again. Not every reflection object is: ReflectionParameter cannot be
 * instantiated, so a definition holding one takes the whole location's cache
 * down with it.
 *
 * That failure is silent in the worst way — the cache write returns false, the
 * application boots anyway, and the only symptom is that discovery runs in full
 * on every single boot. So it is asserted here rather than noticed later.
 */
#[CoversClass(CommandCompiler::class)]
#[CoversClass(OptionDefinition::class)]
final class DefinitionsAreCacheableTest extends TestCase
{
    public function test_a_compiled_command_can_be_exported_as_php(): void
    {
        $definition = $this->definition(PlatformCommand::class);

        $this->assertNotEmpty($definition->options, 'the fixture has to carry options to be worth exporting');

        try {
            $exported = VarExporter::export($definition);
        } catch (Throwable $throwable) {
            $this->fail('A compiled command must be exportable, but: ' . $throwable->getMessage());
        }

        // Round tripped the way the cache itself does it: written as PHP, read back in.
        $file = tempnam(sys_get_temp_dir(), 'definition') . '.php';
        file_put_contents($file, '<?php return ' . $exported . ';');

        try {
            $restored = require $file;
        } finally {
            unlink($file);
        }

        $this->assertEquals($definition, $restored);
        $this->assertSame('platform', $restored->options['platform']->parameter()->getName());
    }

    /**
     * Middleware written as an object inside an attribute is held in the
     * definition as that object, so it goes through the cache the same way an
     * inline autocomplete does — and would take the whole location's cache down
     * with it if it could not be written back out as PHP.
     */
    public function test_middleware_written_inline_survives_the_cache(): void
    {
        $definition = $this->definition(InlineGuardedCommand::class);

        $this->assertNotEmpty($definition->handlers['inline_guarded']->middleware);

        try {
            $exported = VarExporter::export($definition);
        } catch (Throwable $throwable) {
            $this->fail('Middleware written inline must be exportable, but: ' . $throwable->getMessage());
        }

        $file = tempnam(sys_get_temp_dir(), 'definition') . '.php';
        file_put_contents($file, '<?php return ' . $exported . ';');

        try {
            $restored = require $file;
        } finally {
            unlink($file);
        }

        $this->assertEquals($definition, $restored);
        $this->assertSame(
            'Not for you.',
            $restored->handlers['inline_guarded']->middleware[0]->refusal,
        );
    }

    public function test_an_option_still_reaches_the_parameter_it_feeds(): void
    {
        $option = $this->definition(PlatformCommand::class)->options['platform'];

        $this->assertSame('platform', $option->parameterName);
        $this->assertSame('platform', $option->parameter()->getName());
    }
}
