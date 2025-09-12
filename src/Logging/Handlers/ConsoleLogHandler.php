<?php

namespace Tempcord\Logging\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Tempest\Console\Console;
use function Tempest\Support\Arr\map_iterable;
use function Tempest\Support\str;

final class ConsoleLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly Console $console,
        private readonly array   $except = [],
        private readonly bool    $includeTimestamp = true,
        private readonly bool    $includeContext = true,
                                 $level = Level::Debug,
                                 $bubble = true
    )
    {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        // Skip messages that match the except patterns
        if ($this->shouldSkipMessage($record->message)) {
            return;
        }

        $type = $this->getLogLevel($record->level);
        $message = $this->formatMessage($record);
        $component = $this->getComponent($record->level);

        // Use the appropriate console method based on log level
        $this->console->{$component}($message);
    }
    
    private function shouldSkipMessage(string $message): bool
    {
        if (empty($this->except)) {
            return false;
        }
        
        $messageLower = str($message)->lower();
        return $messageLower->contains(
            map_iterable($this->except, fn(string $pattern) => str($pattern)->lower())
        );
    }
    
    private function formatMessage(LogRecord $record): string
    {
        $message = ucfirst($record->message);
        
        if ($this->includeTimestamp) {
            $timestamp = $record->datetime->format('Y-m-d H:i:s');
            $message = "[{$timestamp}] {$message}";
        }
        
        return $message;
    }
    
    private function getLogLevel(Level $level): string
    {
        return match ($level) {
            Level::Alert => LogLevel::ALERT,
            Level::Critical => LogLevel::CRITICAL,
            Level::Debug => LogLevel::DEBUG,
            Level::Emergency => LogLevel::EMERGENCY,
            Level::Error => LogLevel::ERROR,
            Level::Warning => LogLevel::WARNING,
            default => LogLevel::INFO,
        };
    }
    
    private function getComponent(Level $level): string
    {
        return match ($level) {
            Level::Alert, Level::Critical, Level::Error, Level::Emergency => 'error',
            Level::Warning => 'warning',
            default => 'info',
        };
    }
}