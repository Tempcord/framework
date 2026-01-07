<?php

declare(strict_types=1);

namespace Tempcord\Common\Tasks;

use Tempcord\Attributes\Task;
use Tempest\Log\Logger;

/**
 * Simple heartbeat task that logs the bot is still running.
 *
 * Useful for monitoring and debugging long-running bots.
 */
final class HeartbeatLogTask
{
    private int $heartbeatCount = 0;
    private float $startTime;

    public function __construct(
        private readonly Logger $logger,
    ) {
        $this->startTime = microtime(true);
    }

    #[Task(interval: 3600, name: 'heartbeat_log')]
    public function log(): void
    {
        $this->heartbeatCount++;
        $uptime = $this->formatUptime(microtime(true) - $this->startTime);
        $memoryUsage = $this->formatBytes(memory_get_usage(true));
        $peakMemory = $this->formatBytes(memory_get_peak_usage(true));

        $this->logger->info("💓 Heartbeat #{$this->heartbeatCount}", [
            'uptime' => $uptime,
            'memory' => $memoryUsage,
            'peak_memory' => $peakMemory,
        ]);
    }

    private function formatUptime(float $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";

        return implode(' ', $parts) ?: '< 1m';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
