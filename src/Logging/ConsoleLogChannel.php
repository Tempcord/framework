<?php

namespace Tempcord\Logging;

use Monolog\Level;
use Tempcord\Logging\Handlers\ConsoleLogHandler;
use Tempest\Console\Console;
use Tempest\Log\LogChannel;
use function Tempest\get;

final class ConsoleLogChannel implements LogChannel
{
    public function __construct(
        private array $except = [],
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

    public function getHandlers(Level $level): array
    {
        return [
            new ConsoleLogHandler(
                console: get(Console::class),
                except: $this->except,
                includeTimestamp: true,
                level: $level
            ),
        ];
    }

    public function getProcessors(): array
    {
        return [];
    }
}
