<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;

#[Command(description: 'Music controls', translationKey: 'commands.music')]
#[SubcommandGroup(name: 'playlist', description: 'Playlist controls')]
final class LocalizedCommand
{
    #[Subcommand(name: 'play', description: 'Play a track')]
    public function play(
        #[Option(description: 'Track title')] string $title,
    ): void {}
}
