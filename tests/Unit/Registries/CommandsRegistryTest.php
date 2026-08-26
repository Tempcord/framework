<?php

namespace Tempcord\Tests\Unit\Registries;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Tests\Fixtures\GuildAlphaCommand;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\PingCommand;
use Tempcord\Tests\Unit\TestCase;

#[CoversClass(CommandsRegistry::class)]
final class CommandsRegistryTest extends TestCase
{
    public function test_it_stores_a_global_command_under_its_name(): void
    {
        $registry = new CommandsRegistry();
        $registry->add($this->definition(PingCommand::class));

        $this->assertSame(['ping'], array_keys($registry->all()));
    }

    public function test_it_scopes_a_guild_command_by_its_guild(): void
    {
        $registry = new CommandsRegistry();
        $registry->add($this->definition(GuildAlphaCommand::class));

        $this->assertSame(['111:alpha'], array_keys($registry->all()));
    }

    public function test_it_keeps_distinct_commands_side_by_side(): void
    {
        $registry = new CommandsRegistry();
        $registry->add($this->definition(PingCommand::class));
        $registry->add($this->definition(ModerationCommand::class));

        $this->assertSame(['ping', 'moderation'], array_keys($registry->all()));
        $this->assertSame(2, $registry->count());
    }

    public function test_re_adding_the_same_command_name_merges_it(): void
    {
        $registry = new CommandsRegistry();
        $registry->add($this->definition(PingCommand::class));
        $registry->add($this->definition(PingCommand::class));

        $stored = $registry->all();

        $this->assertCount(1, $stored);
        $this->assertSame(['name', 'times'], array_keys($stored['ping']->options));
        $this->assertSame(['ping'], array_keys($stored['ping']->handlers));
    }

    /**
     * The registry is storage and nothing else, so that discovery can build it
     * before the container has the services the runtime needs.
     */
    public function test_it_needs_nothing_to_construct(): void
    {
        $this->assertSame(0, new CommandsRegistry()->count());
    }
}
