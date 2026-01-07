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

        $message = $this->formatRichMessage($record);
        $component = $this->getComponent($record->level);

        // Use the appropriate console method based on log level
        $this->console->{$component}($message);
    }

    private function shouldSkipMessage(string $message): bool
    {
        if (empty($this->except)) {
            return false;
        }

        return str($message)->lower()->contains(
            map_iterable($this->except, fn(string $pattern) => str($pattern)->lower())
        );
    }

    private function formatRichMessage(LogRecord $record): string
    {
        $parts = [];

        // Timestamp with gray color
        if ($this->includeTimestamp) {
            $timestamp = $record->datetime->format('H:i:s');
            $parts[] = "\033[90m[$timestamp]\033[0m"; // Gray
        }

        // Level badge with color and text
        $badge = $this->getLevelBadge($record->level);
        $parts[] = $badge;

        // Main message
        $parts[] = $record->message;

        $firstLine = implode(' ', $parts);

        // Format context if present
        if (!empty($record->context)) {
            $contextLines = $this->formatContext($record->context);
            return $firstLine . "\n" . $contextLines;
        }

        return $firstLine;
    }

    private function getLevelBadge(Level $level): string
    {
        return match ($level) {
            Level::Emergency => "\033[41;97m EMERGENCY \033[0m", // Red background, white text
            Level::Alert => "\033[41;97m ALERT \033[0m",
            Level::Critical => "\033[41;97m CRITICAL \033[0m",
            Level::Error => "\033[41;97m ERROR \033[0m",
            Level::Warning => "\033[43;30m WARNING \033[0m", // Yellow background, black text
            Level::Notice => "\033[44;97m NOTICE \033[0m", // Blue background, white text
            Level::Info => "\033[42;97m INFO \033[0m", // Green background, white text
            Level::Debug => "\033[100;97m DEBUG \033[0m", // Gray background, white text
        };
    }

    private function formatContext(array $context, int $indent = 0): string
    {
        $lines = [];
        $indentStr = str_repeat('  ', $indent);
        $arrow = "\033[90m→\033[0m"; // Gray arrow

        foreach ($context as $key => $value) {
            $coloredKey = "\033[36m{$key}\033[0m"; // Cyan key

            if (is_array($value)) {
                // Nested array - compact format
                $jsonValue = json_encode($value, JSON_UNESCAPED_SLASHES);
                $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: \033[90m{$jsonValue}\033[0m"; // Gray value
            } elseif (is_bool($value)) {
                $valueStr = $value ? "\033[32mtrue\033[0m" : "\033[31mfalse\033[0m"; // Green/Red
                $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: {$valueStr}";
            } elseif (is_null($value)) {
                $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: \033[90mnull\033[0m"; // Gray
            } elseif (is_numeric($value)) {
                $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: \033[33m{$value}\033[0m"; // Yellow
            } elseif (is_string($value)) {
                $truncated = $this->truncateString($value, 150);
                // Check for special patterns
                if ($this->looksLikeId($value)) {
                    $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: \033[35m{$truncated}\033[0m"; // Magenta for IDs
                } elseif ($this->looksLikeFilePath($value)) {
                    $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: \033[36m{$truncated}\033[0m"; // Cyan for paths
                } else {
                    $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: {$truncated}";
                }
            } elseif (is_object($value)) {
                $className = get_class($value);
                $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: \033[35m{$className}\033[0m"; // Magenta
            } else {
                $lines[] = "{$indentStr}  {$arrow} {$coloredKey}: {$value}";
            }
        }

        return implode("\n", $lines);
    }

    private function looksLikeId(string $value): bool
    {
        // Discord snowflake IDs are 17-19 digits
        return preg_match('/^\d{17,19}$/', $value) === 1;
    }

    private function looksLikeFilePath(string $value): bool
    {
        return preg_match('/^[\/\\\\].*\.(php|js|ts|json|yaml|yml)$/i', $value) === 1
            || preg_match('/.*:\d+$/', $value) === 1; // file:line format
    }

    private function truncateString(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 3) . '...';
    }

    private function getComponent(Level $level): string
    {
        return match ($level) {
            Level::Alert, Level::Critical, Level::Error, Level::Emergency => 'error',
            Level::Warning => 'warning',
            default => 'writeln',
        };
    }
}