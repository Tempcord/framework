<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Enums\ApplicationCommandTypes;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\Message;
use Tempcord\Attributes\Command;

#[Command(name: 'Поскаржитись', type: ApplicationCommandTypes::MESSAGE)]
final class ReportContextMenu
{
    /** @var list<string> */
    public static array $targets = [];

    public function __invoke(CommandInteraction $interaction, Message $target): void
    {
        self::$targets[] = $target->id;
    }
}
