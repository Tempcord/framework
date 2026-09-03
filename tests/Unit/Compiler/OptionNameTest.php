<?php

namespace Tempcord\Tests\Unit\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Tests\Fixtures\CamelCasedOptionsCommand;
use Tempcord\Tests\Unit\TestCase;

/**
 * Discord rejects an option name with a capital in it, and rejects the whole
 * set of commands over a single one — in a Bad Request that identifies the
 * offender only by its index in the list. A PHP parameter is idiomatically
 * camelCase, so the name taken from one is snake_cased first, the same way a
 * command already takes its name from its class.
 */
#[CoversClass(CommandCompiler::class)]
final class OptionNameTest extends TestCase
{
    public function test_an_option_takes_a_snake_cased_name_from_its_parameter(): void
    {
        $options = $this->definition(CamelCasedOptionsCommand::class)->options;

        $this->assertArrayHasKey('care_package', $options);
        $this->assertArrayHasKey('how_many_times', $options);
    }

    public function test_a_name_that_needs_no_change_is_left_alone(): void
    {
        $this->assertArrayHasKey(
            'platform',
            $this->definition(CamelCasedOptionsCommand::class)->options,
        );
    }

    public function test_an_explicit_name_is_used_as_written(): void
    {
        $this->assertArrayHasKey(
            'kept_as_written',
            $this->definition(CamelCasedOptionsCommand::class)->options,
        );
    }

    /**
     * The renaming is for Discord's sake; the handler still has to be called
     * with its own parameter.
     */
    public function test_the_option_still_points_at_the_parameter_it_was_named_for(): void
    {
        $option = $this->definition(CamelCasedOptionsCommand::class)->options['care_package'];

        $this->assertSame('carePackage', $option->parameterName);
        $this->assertSame('carePackage', $option->parameter()->getName());
    }

    public function test_no_option_name_would_be_refused_by_discord(): void
    {
        foreach ($this->definition(CamelCasedOptionsCommand::class)->options as $name => $option) {
            $this->assertSame(mb_strtolower($name), $name, $name);
        }
    }
}
