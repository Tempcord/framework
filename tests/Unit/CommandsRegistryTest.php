<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Tempcord\AllCommandExtension;
use Tempcord\Attributes\Command;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempest\Console\Console;

#[CoversClass(CommandsRegistry::class)]
final class CommandsRegistryTest extends TestCase
{
    private function registry(): CommandsRegistry
    {
        return new CommandsRegistry(new AllCommandExtension());
    }

    /**
     * @return array<string, Command>
     */
    private function storedCommands(CommandsRegistry $registry): array
    {
        return new ReflectionProperty(CommandsRegistry::class, 'commands')->getValue($registry);
    }

    public function test_it_stores_a_global_command_under_its_name(): void
    {
        $registry = $this->registry();
        $registry->add($this->command(PingCommand::class));

        $this->assertSame(['ping'], array_keys($this->storedCommands($registry)));
    }

    public function test_it_keeps_distinct_commands_side_by_side(): void
    {
        $registry = $this->registry();
        $registry->add($this->command(PingCommand::class));
        $registry->add($this->command(ModerationCommand::class));

        $this->assertSame(['ping', 'moderation'], array_keys($this->storedCommands($registry)));
    }

    public function test_re_adding_the_same_command_name_merges_the_options(): void
    {
        $registry = $this->registry();
        $registry->add($this->command(PingCommand::class));
        $registry->add($this->command(PingCommand::class));

        $stored = $this->storedCommands($registry);

        $this->assertCount(1, $stored);
        $this->assertSame(['name', 'times'], array_keys($stored['ping']->options));
    }

    public function test_register_warns_instead_of_calling_discord_when_there_is_nothing_to_register(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('warning')
            ->with('No commands to register.');

        // A null Discord would fatal if it were touched; it never should be.
        $this->registry()->register($console, $this->createStub(\Ragnarok\Fenrir\Discord::class));
    }
}
