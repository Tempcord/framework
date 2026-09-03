<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Interfaces\Middleware;

/**
 * Never calls $next, which is how a middleware says no.
 */
final class RefusingMiddleware implements Middleware
{
    public function __invoke(CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction, callable $next): void
    {
        TrailMiddleware::$trail[] = 'refused';
    }
}
