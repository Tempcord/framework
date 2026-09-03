<?php

namespace Tempcord\Tests\Unit\Logging;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Logging\ConsoleLogChannel;
use Tempest\Console\Console;
use Tempest\Container\GenericContainer;

/**
 * The logger builds a handler for each level it writes and hands it that
 * record's own level. Taken as the handler's threshold, that admits everything
 * — which is how a REST layer logging every request it sends and every bucket
 * it queues one in came to bury the lines somebody was watching for.
 */
#[CoversClass(ConsoleLogChannel::class)]
final class ConsoleLogChannelTest extends BaseTestCase
{
    protected function setUp(): void
    {
        $container = new GenericContainer();
        $container->singleton(Console::class, $this->createStub(Console::class));

        GenericContainer::setInstance($container);
    }

    protected function tearDown(): void
    {
        GenericContainer::setInstance(null);
    }

    private function debugRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'tempcord',
            level: Level::Debug,
            message: 'BUCKET getchannels/1/messages queued REQ GET channels/1/messages',
        );
    }

    public function test_the_handler_keeps_the_configured_minimum_not_the_records_level(): void
    {
        $handler = new ConsoleLogChannel(minimum: Level::Info)->getHandlers(Level::Debug)[0];

        $this->assertFalse($handler->isHandling($this->debugRecord()));
    }

    public function test_it_lets_debug_through_when_configured_to(): void
    {
        $handler = new ConsoleLogChannel(minimum: Level::Debug)->getHandlers(Level::Error)[0];

        $this->assertTrue($handler->isHandling($this->debugRecord()));
    }

    public function test_info_is_the_default(): void
    {
        $handler = new ConsoleLogChannel()->getHandlers(Level::Debug)[0];

        $this->assertFalse($handler->isHandling($this->debugRecord()));
    }
}
