<?php

namespace Tempcord\Runtime;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\GuildMember;
use Tempcord\Discord\Parts\Message;
use Tempcord\Discord\Parts\User;
use Tempest\Reflection\ParameterReflector;

/**
 * What a context menu was used on.
 *
 * A context menu carries no options — Discord names the target in
 * `data.target_id` and puts the object itself in `data.resolved`. So there is
 * nothing for the option resolver to find, and without this a handler could
 * only take the raw interaction and dig the target out by hand.
 *
 * The parameter's own type says which shape is wanted, the same way it does
 * for a component interaction.
 */
final readonly class TargetResolver
{
    /**
     * The target as this parameter asks for it.
     *
     * Returns null when the interaction is not a context menu, or the
     * parameter is typed as something a target is never delivered as — in
     * which case the caller falls back to the parameter's own default.
     */
    public function resolve(CommandInteraction $interaction, ParameterReflector $parameter): ?object
    {
        $data = $interaction->interaction->data ?? null;
        $targetId = $data->target_id ?? null;

        if ($targetId === null || !$parameter->getReflection()->hasType()) {
            return null;
        }

        return match ($parameter->getType()->getName()) {
            User::class => $data->resolved->users[$targetId] ?? null,
            Message::class => $data->resolved->messages[$targetId] ?? null,
            GuildMember::class => $this->member($interaction, $targetId),
            default => null,
        };
    }

    /**
     * The member a user context menu was used on.
     *
     * Discord sends the member and the user separately and leaves the member's
     * own `user` empty, since it would only repeat what is already there. A
     * handler asking for a member still expects to reach their id, so the two
     * halves are put back together.
     */
    private function member(CommandInteraction $interaction, string $targetId): ?GuildMember
    {
        $data = $interaction->interaction->data;
        $member = $data->resolved->members[$targetId] ?? null;

        if ($member === null) {
            return null;
        }

        $member->user ??= $data->resolved->users[$targetId] ?? null;

        return $member;
    }
}
