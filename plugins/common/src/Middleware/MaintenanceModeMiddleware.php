<?php

declare(strict_types=1);

namespace Tempcord\Common\Middleware;

use Tempcord\CommandInteraction;
use Tempcord\Common\CommonConfig;
use Tempcord\Middleware\CommandMiddleware;

/**
 * Middleware that blocks all commands when maintenance mode is enabled.
 *
 * Bot owners and configured bypass users can still use commands.
 *
 * Enable maintenance mode via CommonConfig:
 * ```php
 * $config->withMaintenance(true, 'Upgrading to version 2.0...');
 * ```
 */
final readonly class MaintenanceModeMiddleware implements CommandMiddleware
{
    public function __construct(
        private CommonConfig $config,
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        if (!$this->config->maintenanceMode) {
            return $next($interaction);
        }

        $userId = $interaction->member?->user?->id ?? $interaction->user?->id;

        if ($userId !== null && $this->config->canBypassMaintenance($userId)) {
            return $next($interaction);
        }

        return $interaction->respond()
            ->warning()
            ->title('🔧 Maintenance Mode')
            ->content($this->config->maintenanceMessage)
            ->ephemeral()
            ->send();
    }
}
