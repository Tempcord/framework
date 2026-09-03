<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;
use Tempcord\Discord\Interaction\CommandInteraction;

/**
 * One command, two audiences: anybody may make a suggestion, only moderation
 * may act on the ones that are in.
 *
 * Discord scopes a command's permissions to the whole command, so this is not
 * something #[Command(permissions: ...)] can describe at all.
 */
#[Command(description: 'Suggestions from the server')]
final class SuggestionCommand
{
    #[Subcommand(name: 'add', description: 'Suggest something.')]
    public function add(CommandInteraction $interaction): void {}

    #[Subcommand(
        name: 'close',
        description: 'Close a suggestion.',
        middleware: [ModerationOnly::class],
    )]
    public function close(CommandInteraction $interaction): void {}
}
