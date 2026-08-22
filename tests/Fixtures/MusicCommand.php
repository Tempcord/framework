<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;

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
