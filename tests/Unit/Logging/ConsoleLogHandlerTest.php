<?php

namespace Tempcord\Tests\Unit\Logging;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Logging\Handlers\ConsoleLogHandler;
use Tempest\Console\Console;

#[CoversClass(ConsoleLogHandler::class)]
final class ConsoleLogHandlerTest extends BaseTestCase
{
    private function record(string $message, array $context = [], Level $level = Level::Info): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable('2026-01-02 03:04:05'),
            channel: 'tempcord',
            level: $level,
            message: $message,
            context: $context,
        );
    }

    /**
     * The REST layer logs every request it sends and every bucket it queues one
     * in, all at debug. Left through, four lines of that arrive for each thing
     * the bot does, and a warning in the middle of them goes unread.
     */
    public function test_it_writes_nothing_below_its_level(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->never())->method('info');

        new ConsoleLogHandler($console, level: Level::Info)
            ->handle($this->record('BUCKET queued REQ GET channels/1/messages', level: Level::Debug));
    }

    public function test_it_writes_what_is_at_or_above_its_level(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())->method('warning');

        new ConsoleLogHandler($console, includeTimestamp: false, includeContext: false, level: Level::Info)
            ->handle($this->record('rate limited', level: Level::Warning));
    }

    public function test_debug_still_gets_through_when_that_is_what_was_asked_for(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())->method('info');

        new ConsoleLogHandler($console, includeTimestamp: false, includeContext: false, level: Level::Debug)
            ->handle($this->record('a detail', level: Level::Debug));
    }

    public function test_it_prefixes_the_timestamp_when_asked(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('info')
            ->with('[2026-01-02 03:04:05] Connected');

        new ConsoleLogHandler($console, includeContext: false)->handle($this->record('connected'));
    }

    public function test_it_omits_the_timestamp_when_disabled(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('info')
            ->with('Connected');

        new ConsoleLogHandler($console, includeTimestamp: false, includeContext: false)
            ->handle($this->record('connected'));
    }

    public function test_it_appends_the_record_context(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('info')
            ->with('Connected {"guild_id":"123","shard":0}');

        new ConsoleLogHandler($console, includeTimestamp: false)
            ->handle($this->record('connected', ['guild_id' => '123', 'shard' => 0]));
    }

    public function test_it_appends_nothing_for_an_empty_context(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('info')
            ->with('Connected');

        new ConsoleLogHandler($console, includeTimestamp: false)->handle($this->record('connected'));
    }

    public function test_context_can_be_switched_off(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('info')
            ->with('Connected');

        new ConsoleLogHandler($console, includeTimestamp: false, includeContext: false)
            ->handle($this->record('connected', ['guild_id' => '123']));
    }

    public function test_it_skips_messages_matching_an_except_pattern(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->never())->method('info');

        new ConsoleLogHandler($console, except: ['sending heartbeat'])
            ->handle($this->record('Sending heartbeat now'));
    }

    public function test_it_routes_by_severity(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())->method('error')->with('Boom');
        $console->expects($this->once())->method('warning')->with('Careful');

        $handler = new ConsoleLogHandler($console, includeTimestamp: false, includeContext: false);
        $handler->handle($this->record('boom', level: Level::Error));
        $handler->handle($this->record('careful', level: Level::Warning));
    }

    /**
     * A long message carrying the literal "<style=" used to exhaust the regex
     * engine inside Tempest's console parser, which then handed null to
     * preg_match and killed the process. Log messages carry response bodies, so
     * this is reachable from anything Discord returns.
     */
    public function test_a_message_that_looks_like_console_markup_is_neutralised(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('warning')
            ->with($this->callback(static fn(string $written) => !str_contains($written, '<style')));

        new ConsoleLogHandler($console, includeContext: false)
            ->handle($this->record('<style="fg-red">' . str_repeat('a', 100), level: Level::Warning));
    }

    public function test_a_very_long_message_is_truncated(): void
    {
        $console = $this->createMock(Console::class);
        $console->expects($this->once())
            ->method('info')
            ->with($this->callback(static fn(string $written) => mb_strlen($written) < 2200
                && str_contains($written, 'characters)')));

        new ConsoleLogHandler($console, includeContext: false)
            ->handle($this->record(str_repeat('a', 5000)));
    }
}
