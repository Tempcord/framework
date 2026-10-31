<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;
use Tempcord\Declare\Command\Subcommand;
use Tempcord\Declare\Command\SubcommandGroup;

#[Command(description: 'Music controls', translationKey: 'commands.music')]
#[SubcommandGroup(name: 'playlist', description: 'Playlist controls')]
final class LocalizedCommand
{
    #[Subcommand(name: 'play', description: 'Play a track')]
    public function play(
        #[Option(description: 'Track title')] string $title,
    ): void {}
}
