<?php

declare(strict_types=1);

namespace Tempcord\Common\Middleware;

use Tempcord\CommandInteraction;
use Tempcord\Middleware\CommandMiddleware;

/**
 * Middleware that ensures commands are only executed in NSFW channels.
 *
 * Use this for age-restricted commands.
 *
 * @example
 * ```php
 * #[Command(middleware: [NsfwChannelMiddleware::class])]
 * class AdultContentCommand { ... }
 * ```
 */
final class NsfwChannelMiddleware implements CommandMiddleware
{
    public function handle(CommandInteraction $interaction, callable $next): mixed
    {
        // Check if channel is NSFW
        // The channel data should be available from the interaction
        $channel = $interaction->channel ?? null;

        if ($channel === null || !($channel->nsfw ?? false)) {
            return $interaction->respond()
                ->error()
                ->title('Age-Restricted Content')
                ->content('This command can only be used in age-restricted (NSFW) channels.')
                ->ephemeral()
                ->send();
        }

        return $next($interaction);
    }
}
