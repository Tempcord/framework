<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\User;
use Tempcord\Attributes\Command;

/**
 * A context menu on a user. Discord shows the name verbatim, so it is given
 * rather than derived from the class.
 */
#[Command(name: 'Профіль', type: ApplicationCommandTypes::USER)]
final class ProfileContextMenu
{
    /** @var list<string> */
    public static array $targets = [];

    public function __invoke(CommandInteraction $interaction, User $target): void
    {
        self::$targets[] = $target->id;
    }
}
