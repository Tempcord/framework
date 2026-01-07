<?php

namespace Tempcord\Middleware;

use Tempcord\CommandInteraction;

interface CommandMiddleware
{
    public function handle(CommandInteraction $interaction, callable $next): mixed;
}
