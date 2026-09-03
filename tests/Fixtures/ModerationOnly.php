<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Interfaces\Middleware;

/**
 * Lets only a member holding the moderation role through.
 *
 * Named by class rather than written inline, so the container builds it and it
 * can take the configuration that knows which role that is.
 */
final readonly class ModerationOnly implements Middleware
{
    public function __construct(
        private string $moderatorRole = '::moderator::',
    ) {}

    public function __invoke(
        CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction,
        callable $next,
    ): void {
        $roles = $interaction->interaction->member->roles ?? [];

        if (!in_array($this->moderatorRole, $roles, true)) {
            $interaction->reply('Only moderation may do that.', ephemeral: true);

            return;
        }

        $next($interaction);
    }
}
