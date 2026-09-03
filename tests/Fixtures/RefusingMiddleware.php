<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Interfaces\Middleware;

/**
 * Never calls $next, which is how a middleware says no.
 */
final class RefusingMiddleware implements Middleware
{
    public function __invoke(CommandInteraction $interaction, callable $next): void
    {
        TrailMiddleware::$trail[] = 'refused';
    }
}
