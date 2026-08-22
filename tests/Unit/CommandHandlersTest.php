<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Tempcord\Attributes\Command;
use Tempcord\Tests\Fixtures\BareCommand;
use Tempcord\Tests\Fixtures\ModerationCommand;
use Tempcord\Tests\Fixtures\MusicCommand;
use Tempcord\Tests\Fixtures\PingCommand;

#[CoversClass(Command::class)]
final class CommandHandlersTest extends TestCase
{
    public function test_an_invokable_command_is_bound_under_its_own_name(): void
    {
        $handlers = $this->command(PingCommand::class)->handlers;

        $this->assertSame(['ping'], array_keys($handlers));
    }

    /**
     * A command whose __invoke declares no options still has to be listened
     * for — otherwise it registers with Discord and then silently never fires.
     */
    public function test_an_invokable_command_without_options_is_still_bound(): void
    {
        $handlers = $this->command(BareCommand::class)->handlers;

        $this->assertSame(['bare'], array_keys($handlers));
    }

    public function test_subcommands_are_bound_under_dotted_paths(): void
    {
        $handlers = $this->command(ModerationCommand::class)->handlers;

        $this->assertSame(['moderation.kick'], array_keys($handlers));
    }

    public function test_grouped_subcommands_are_bound_under_the_group(): void
    {
        $handlers = $this->command(MusicCommand::class)->handlers;

        $this->assertSame(
            ['music.playlist.play', 'music.playlist.stop'],
            array_keys($handlers),
        );
    }
}
