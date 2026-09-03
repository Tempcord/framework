<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Interfaces\Middleware;

/**
 * Keeps the interaction it was handed, so a test can say which shape of it a
 * middleware sees.
 */
final class RecordingInteractionMiddleware implements Middleware
{
    /** @var list<object> */
    public static array $seen = [];

    public function __invoke(
        CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction,
        callable $next,
    ): void {
        self::$seen[] = $interaction;

        $next($interaction);
    }
}
