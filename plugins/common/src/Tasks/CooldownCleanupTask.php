<?php

declare(strict_types=1);

namespace Tempcord\Common\Tasks;

use Tempcord\Attributes\Task;
use Tempcord\Common\Middleware\CooldownMiddleware;
use Tempest\Log\Logger;

/**
 * Periodic cleanup task for cooldown data.
 *
 * Runs every hour to clear expired cooldown entries from memory.
 */
final class CooldownCleanupTask
{
    public function __construct(
        private readonly Logger $logger,
    ) {}

    #[Task(cron: '0 * * * *', name: 'cooldown_cleanup')]
    public function cleanup(): void
    {
        CooldownMiddleware::resetAllCooldowns();
        $this->logger->debug('Cooldown cache cleared');
    }
}
