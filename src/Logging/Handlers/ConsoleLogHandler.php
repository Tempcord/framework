<?php

namespace Tempcord\Logging\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LogLevel;
use Tempest\Console\Console;
use function Tempest\Support\Arr\map;
use function Tempest\Support\str;

final class ConsoleLogHandler extends AbstractProcessingHandler
{
    /**
     * Beyond this a log line is truncated; nothing useful is read off a console
     * past it, and it bounds the work the markup parser has to do.
     */
    private const int MAX_LENGTH = 2000;

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
        $this->console->{$component}($this->safeForConsole($message));
    }
    
    private function shouldSkipMessage(string $message): bool
    {
        if (empty($this->except)) {
            return false;
        }
        
        $messageLower = str($message)->lower();
        return $messageLower->contains(
            map($this->except, fn(string $pattern) => str($pattern)->lower())
        );
    }
    
    /**
     * Log messages are data, not markup.
     *
     * Tempest's console parses <style> tags out of whatever it is given, and
     * these messages carry whatever the gateway and REST layer logged —
     * including response bodies. A long message containing the literal
     * "<style=" exhausts the regex engine's stack, at which point the parser
     * hands null to preg_match and the process dies on a TypeError. Breaking
     * the sequence keeps the tag from ever matching, and the length cap keeps a
     * single log line from filling a terminal.
     */
    private function safeForConsole(string $message): string
    {
        $message = str_replace('<style', '< style', $message);

        if (mb_strlen($message) <= self::MAX_LENGTH) {
            return $message;
        }

        return mb_substr($message, 0, self::MAX_LENGTH) . '… (' . mb_strlen($message) . ' characters)';
    }

    private function formatMessage(LogRecord $record): string
    {
        $message = ucfirst($record->message);
        
        if ($this->includeTimestamp) {
            $timestamp = $record->datetime->format('Y-m-d H:i:s');
            $message = "[{$timestamp}] {$message}";
        }

        if ($this->includeContext && $record->context !== []) {
            $context = json_encode($record->context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            $message = "{$message} {$context}";
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