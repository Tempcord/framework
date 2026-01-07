<?php

declare(strict_types=1);

namespace Tempcord\Common\Middleware;

use Tempcord\CommandInteraction;
use Tempcord\Middleware\CommandMiddleware;

/**
 * Middleware that ensures commands are only executed in guild (server) contexts.
 *
 * Use this middleware on commands that require guild features like roles,
 * channels, or member management.
 *
 * @example
 * ```php
 * #[Command(middleware: [GuildOnlyMiddleware::class])]
 * class ServerInfoCommand { ... }
 * ```
 */
final class GuildOnlyMiddleware implements CommandMiddleware
{
    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        if ($interaction->guildId === null) {
            return $interaction->respond()
                ->error()
                ->title('Server Only')
                ->content('This command can only be used in a server, not in DMs.')
                ->ephemeral()
                ->send();
        }

        return $next($interaction);
    }
}
