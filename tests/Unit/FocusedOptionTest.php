<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use ReflectionMethod;
use Tempcord\AllCommandExtension;
use Tempcord\Attributes\Command;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\PingCommand;

#[CoversClass(CommandsRegistry::class)]
final class FocusedOptionTest extends TestCase
{
    private function option(string $name, bool $focused = false): ApplicationCommandInteractionDataOptionStructure
    {
        $option = new ApplicationCommandInteractionDataOptionStructure();
        $option->name = $name;
        $option->type = ApplicationCommandOptionType::STRING;
        $option->value = 'whatever';
        $option->options = [];
        $option->focused = $focused;

        return $option;
    }

    private function resolve(array $interactionOptions, Command $definition): ?array
    {
        return new ReflectionMethod(CommandsRegistry::class, 'resolveFocusedAndParam')
            ->invoke(new CommandsRegistry(new AllCommandExtension()), $interactionOptions, $definition);
    }

    public function test_it_resolves_the_focused_option_against_the_definition(): void
    {
        $command = $this->command(PingCommand::class);
        $focused = $this->option('name', focused: true);

        $resolved = $this->resolve([$this->option('times'), $focused], $command);

        $this->assertNotNull($resolved);
        $this->assertSame($command->options['name'], $resolved[0]);
        $this->assertSame($focused, $resolved[1]);
    }

    public function test_it_returns_null_when_nothing_is_focused(): void
    {
        $this->assertNull(
            $this->resolve([$this->option('name'), $this->option('times')], $this->command(PingCommand::class)),
        );
    }

    /**
     * A focused option the command does not declare must not raise an
     * "Undefined array key" warning — it simply does not resolve.
     */
    public function test_a_focused_option_unknown_to_the_definition_resolves_to_null(): void
    {
        $this->assertNull(
            $this->resolve([$this->option('not_declared', focused: true)], $this->command(PingCommand::class)),
        );
    }

    public function test_it_returns_null_for_no_options_at_all(): void
    {
        $this->assertNull($this->resolve([], $this->command(PingCommand::class)));
    }

    /**
     * Discord nests the focused option inside the subcommand group and then
     * the subcommand, so resolution has to drill through both.
     */
    public function test_it_drills_through_a_group_and_a_subcommand(): void
    {
        $command = $this->command(MusicCommand::class);

        $focused = $this->option('title', focused: true);
        $play = $this->option('play');
        $play->type = ApplicationCommandOptionType::SUB_COMMAND;
        $play->options = [$focused];
        $playlist = $this->option('playlist');
        $playlist->type = ApplicationCommandOptionType::SUB_COMMAND_GROUP;
        $playlist->options = [$play];

        $resolved = $this->resolve([$playlist], $command);

        $this->assertNotNull($resolved);
        $this->assertSame('title', $resolved[0]->name);
        $this->assertSame('Track title', $resolved[0]->description);
        $this->assertSame($focused, $resolved[1]);
    }

    public function test_a_subcommand_the_definition_does_not_know_resolves_to_null(): void
    {
        $unknown = $this->option('not_a_group');
        $unknown->type = ApplicationCommandOptionType::SUB_COMMAND_GROUP;
        $unknown->options = [$this->option('title', focused: true)];

        $this->assertNull($this->resolve([$unknown], $this->command(MusicCommand::class)));
    }
}
