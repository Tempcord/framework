<?php

declare(strict_types=1);

namespace Tempcord\Common\Middleware;

use Tempcord\CommandInteraction;
use Tempcord\Middleware\CommandMiddleware;

/**
 * Middleware that ensures commands are only executed in DMs.
 *
 * Use this for private commands like account settings or sensitive operations.
 *
 * @example
 * ```php
 * #[Command(middleware: [DirectMessageOnlyMiddleware::class])]
 * class AccountSettingsCommand { ... }
 * ```
 */
final class DirectMessageOnlyMiddleware implements CommandMiddleware
{
    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        if ($interaction->guildId !== null) {
            return $interaction->respond()
                ->warning()
                ->title('DMs Only')
                ->content('This command can only be used in direct messages for privacy.')
                ->ephemeral()
                ->send();
        }

        return $next($interaction);
    }
}
