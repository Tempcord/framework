<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Attributes\Command;
use Tempcord\Tests\Fixtures\CommandUserSettings;
use Tempcord\Tests\Fixtures\EnumNamedCommand;
use Tempcord\Tests\Fixtures\NamedCommand;
use Tempcord\Tests\Fixtures\PingCommand;

#[CoversClass(Command::class)]
final class CommandNameTest extends TestCase
{
    public function test_it_derives_a_snake_cased_name_from_the_class_name(): void
    {
        $this->assertSame('ping', $this->command(PingCommand::class)->name);
    }

    public function test_it_strips_a_command_prefix_as_well_as_a_suffix(): void
    {
        $this->assertSame('user_settings', $this->command(CommandUserSettings::class)->name);
    }

    public function test_an_explicit_name_wins_over_the_class_name(): void
    {
        $this->assertSame('explicit', $this->command(NamedCommand::class)->name);
    }

    public function test_a_backed_enum_name_is_unwrapped_to_its_value(): void
    {
        $this->assertSame('weather', $this->command(EnumNamedCommand::class)->name);
    }
}
