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
}
