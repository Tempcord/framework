<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;
use Tempcord\Declare\Command\Subcommand;
use Tempcord\Declare\Command\SubcommandGroup;

#[Command(description: 'Music controls')]
#[SubcommandGroup(name: 'playlist', description: 'Playlist controls')]
final class MusicCommand
{
    #[Subcommand(name: 'play', description: 'Play a track')]
    public function play(
        #[Option(description: 'Track title')] string $title,
    ): string {
        return 'playing ' . $title;
    }

    #[Subcommand(name: 'stop', description: 'Stop playback')]
    public function stop(): string
    {
        return 'stopped';
    }

    public function notASubcommand(): void {}
}
