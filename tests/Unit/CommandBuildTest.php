<?php

namespace Tempcord\Tests\Unit;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use RuntimeException;
use Tempcord\Attributes\Command;
use Tempcord\Tests\Fixtures\DescriptionlessCommand;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\NamedCommand;
use Tempcord\Tests\Fixtures\NoHandlerCommand;
use Tempcord\Tests\Fixtures\PingCommand;

#[CoversClass(Command::class)]
final class CommandBuildTest extends TestCase
{
    public function test_it_builds_a_chat_input_command(): void
    {
        $builder = $this->command(PingCommand::class)->build;
        $built = $builder->get();

        $this->assertSame('ping', $built['name']);
        $this->assertSame('Replies with pong', $built['description']);
        $this->assertFalse($builder->getNsfw());
        $this->assertTrue($builder->getDmPermission());
    }

    public function test_a_chat_input_command_requires_a_description(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Description for command [descriptionless] is required when type=CHAT_INPUT');

        $this->command(DescriptionlessCommand::class)->build;
    }

    public function test_an_invokable_command_exposes_its_invoke_parameters_as_options(): void
    {
        $options = $this->command(PingCommand::class)->options;

        $this->assertSame(['name', 'times'], array_keys($options));
        $this->assertTrue($options['name']->isRequired);
        $this->assertFalse($options['times']->isRequired);
    }

    public function test_a_grouped_command_exposes_the_group_as_its_only_option(): void
    {
        $options = $this->command(MusicCommand::class)->options;

        $this->assertSame(['playlist'], array_keys($options));
        $this->assertSame(
            ApplicationCommandOptionType::SUB_COMMAND_GROUP->value,
            $options['playlist']->build->get()['type'],
        );
    }

    public function test_an_ungrouped_command_exposes_its_subcommands_directly(): void
    {
        $options = $this->command(ModerationCommand::class)->options;

        $this->assertSame(['kick'], array_keys($options));
        $this->assertSame(
            ApplicationCommandOptionType::SUB_COMMAND->value,
            $options['kick']->build->get()['type'],
        );
    }

    /**
     * An option-less slash command is perfectly normal and must not blow up
     * on the way to a builder.
     */
    public function test_a_command_without_any_options_builds_cleanly(): void
    {
        $command = $this->command(NamedCommand::class);

        $this->assertSame([], $command->options);
        $this->assertSame('explicit', $command->build->get()['name']);
    }

    public function test_a_command_with_neither_subcommands_nor_invoke_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('should declare public sub-commands or have an __invoke method');

        $this->command(NoHandlerCommand::class)->options;
    }
}
