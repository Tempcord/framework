<?php

namespace Tempcord\Logging;

use Monolog\Level;
use Tempcord\Logging\Handlers\ConsoleLogHandler;
use Tempest\Console\Console;
use Tempest\Log\LogChannel;
use function Tempest\Container\get;

final class ConsoleLogChannel implements LogChannel
{
    /**
     * @param Level $minimum the lowest level that reaches the console
     */
    public function __construct(
        private array $except = [],
        private Level $minimum = Level::Info,
    ) {
        $this->except = array_merge($this->except, [
            'Fenrir initialized.',
            'sending heartbeat',
            'received heartbeat',
            'http not checking',
            'resetting payload count',
            'Client: Connection esablished',
            'Client: Attempting connection',
            'Server: New message',
            'Client: New message',
            'Client: Queued message',
            'Started heartbeat timer'
        ]);
    }

    /**
     * The handler is given the configured minimum, not the level of the record
     * being written.
     *
     * The logger builds a handler per level and hands it that record's own
     * level, which as a threshold admits everything: a REST layer that logs
     * every request and every bucket it queues them in drowns out the lines
     * somebody is actually watching for. Debug output is still one setting
     * away, which is where it belongs.
     */
    public function getHandlers(Level $level): array
    {
        return [
            new ConsoleLogHandler(
                console: get(Console::class),
                except: $this->except,
                includeTimestamp: true,
                level: $this->minimum
            ),
        ];
    }

    public function getProcessors(): array
    {
        return [];
    }
}
