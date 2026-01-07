<?php

declare(strict_types=1);

namespace Tempcord\Common\Middleware;

use Tempcord\CommandInteraction;
use Tempcord\Common\CommonConfig;
use Tempcord\Middleware\CommandMiddleware;

/**
 * Middleware that restricts commands to bot owners only.
 *
 * Configure owner IDs in CommonConfig.
 *
 * @example
 * ```php
 * #[Command(middleware: [BotOwnerOnlyMiddleware::class])]
 * class EvalCommand { ... }
 * ```
 */
final readonly class BotOwnerOnlyMiddleware implements CommandMiddleware
{
    public function __construct(
        private CommonConfig $config,
    ) {}

    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        $userId = $interaction->member?->user?->id ?? $interaction->user?->id;

        if ($userId === null || !$this->config->isOwner($userId)) {
            return $interaction->respond()
                ->error()
                ->title('Access Denied')
                ->content('This command is restricted to bot owners.')
                ->ephemeral()
                ->send();
        }

        return $next($interaction);
    }
}
