<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\GuildMember;
use Tempcord\Attributes\Command;

#[Command(name: 'Ролі', type: ApplicationCommandTypes::USER)]
final class MemberContextMenu
{
    /** @var list<GuildMember> */
    public static array $targets = [];

    public function __invoke(CommandInteraction $interaction, GuildMember $target): void
    {
        self::$targets[] = $target;
    }
}
