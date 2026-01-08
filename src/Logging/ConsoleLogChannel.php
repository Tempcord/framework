<?php

namespace Tempcord\Logging;

use Monolog\Level;
use Tempcord\Logging\Handlers\ConsoleLogHandler;
use Tempcord\Support\InteractiveModeService;
use Tempest\Console\Console;
use Tempest\Core\Environment;
use Tempest\Log\LogChannel;
use function Tempest\get;

final class ConsoleLogChannel implements LogChannel
{
    public function __construct(
        private array $except = [],
    ) {
        $this->except = array_merge($this->except, [
            'sending heartbeat',
            'received heartbeat',
            'http not checking',
            'resetting payload count',
            'Client: Attempting connection',
            'Client: New message',
            'Client: Queued message',
            'Started heartbeat timer',
            'REQ POST',
            'REQ GET',
            'waiting:',
            'empty:',
        ]);
    }

    public function getHandlers(Level $level): array
    {
        // Disable console logging when in interactive mode
        try {
            $interactiveMode = get(InteractiveModeService::class);
            if ($interactiveMode->isEnabled()) {
                return []; // No console logging in interactive mode
            }
        } catch (\Throwable) {
            // Service not available, continue with normal logging
        }

        // Adjust log level based on environment
        $effectiveLevel = $this->getEffectiveLevel();

        return [
            new ConsoleLogHandler(
                console: get(Console::class),
                except: $this->except,
                includeTimestamp: true,
                level: $effectiveLevel
            ),
        ];
    }
    
    private function getEffectiveLevel(): Level
    {
        // Check if we're in production environment
        $isProduction = Environment::fromEnv()->isProduction();

        if ($isProduction) {
            // In production: use Info level (excludes Debug)
            // This includes: Info, Notice, Warning, Error, Critical, Alert, Emergency
            return Level::Info;
        }
        
        // In non-production: use Debug level (includes all levels)
        return Level::Debug;
    }

    public function getProcessors(): array
    {
        return [];
    }
}
