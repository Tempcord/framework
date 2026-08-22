<?php

namespace Tempcord\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempest\Reflection\ClassReflector;

#[CoversClass(Subcommand::class)]
#[CoversClass(SubcommandGroup::class)]
final class SubcommandTest extends TestCase
{
    private function group(): SubcommandGroup
    {
        $reflector = new ClassReflector(MusicCommand::class);

        /** @var SubcommandGroup $group */
        $group = $reflector->getAttribute(SubcommandGroup::class);
        $group->reflector = $reflector;

        return $group;
    }

    public function test_a_group_collects_only_annotated_methods(): void
    {
        $this->assertSame(['play', 'stop'], array_keys($this->group()->options));
    }

    public function test_a_group_builds_a_sub_command_group_option(): void
    {
        $built = $this->group()->build->get();

        $this->assertSame('playlist', $built['name']);
        $this->assertSame('Playlist controls', $built['description']);
        $this->assertSame(ApplicationCommandOptionType::SUB_COMMAND_GROUP->value, $built['type']);
        $this->assertCount(2, $built['options']);
    }

    public function test_a_subcommand_builds_a_sub_command_option_carrying_its_own_options(): void
    {
        $built = $this->group()->options['play']->build->get();

        $this->assertSame('play', $built['name']);
        $this->assertSame(ApplicationCommandOptionType::SUB_COMMAND->value, $built['type']);
        $this->assertCount(1, $built['options']);
        $this->assertSame('title', $built['options'][0]['name']);
    }

    public function test_a_subcommand_without_parameters_has_no_options(): void
    {
        $this->assertSame([], $this->group()->options['stop']->options);
    }

    public function test_invoke_named_args_reorders_arguments_to_the_signature(): void
    {
        $result = $this->group()->options['play']->invokeNamedArgs(
            new MusicCommand(),
            ['unused' => 'ignored', 'title' => 'Bohemian Rhapsody'],
        );

        $this->assertSame('playing Bohemian Rhapsody', $result);
    }

    public function test_invoke_named_args_rejects_a_missing_required_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter: title');

        $this->group()->options['play']->invokeNamedArgs(new MusicCommand(), []);
    }
}
