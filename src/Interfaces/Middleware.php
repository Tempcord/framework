<?php

namespace Tempcord\Interfaces;

use Tempcord\Discord\Interaction\CommandInteraction;

/**
 * Something that runs before a command, and may decide it never runs.
 *
 * Built by the container when it is named by class, so a middleware may take
 * whatever it needs — the configuration holding a guild's roles, a clock, a
 * logger. Written as an instance inside the attribute it takes nothing, which
 * is the right shape for a check that only needs its own arguments.
 *
 * Answering the interaction instead of calling $next stops the command there.
 * That is the whole point for anything that checks a caller's right to be
 * asking: the handler is never reached, so it cannot half-do the work it was
 * asked for — and its options are never resolved, which for an option Discord
 * only sends the id of means a request that is never made.
 */
interface Middleware
{
    /**
     * @param callable(CommandInteraction): void $next
     */
    public function __invoke(CommandInteraction $interaction, callable $next): void;
}
