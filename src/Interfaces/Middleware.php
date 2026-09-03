<?php

namespace Tempcord\Interfaces;

use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;

/**
 * Something that runs before a handler, and may decide it never runs.
 *
 * Built by the container when it is named by class, so a middleware may take
 * whatever it needs — the configuration holding a guild's roles, a clock, a
 * logger. Written as an instance inside the attribute it takes nothing, which
 * is the right shape for a check that only needs its own arguments.
 *
 * Answering the interaction instead of calling $next stops the handler there.
 * That is the whole point for anything that checks a caller's right to be
 * asking: the handler is never reached, so it cannot half-do the work it was
 * asked for — and a command's options are never resolved, which for an option
 * Discord only sends the id of means a request that is never made.
 *
 * The parameter is a union rather than one type because the same guard belongs
 * on a subcommand and on the button that does the same thing: "may this person
 * do this" is one question, and having to write it twice is how the two answers
 * drift apart. Every interaction in the union carries the gateway event as
 * $interaction and can answer, which is all a guard needs.
 */
interface Middleware
{
    /**
     * @param callable(CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction): void $next
     */
    public function __invoke(
        CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction,
        callable $next,
    ): void;
}
