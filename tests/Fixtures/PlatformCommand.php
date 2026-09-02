<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Discord\Interaction\CommandInteraction;

#[Command(description: 'Looks a player up.')]
final class PlatformCommand
{
    public static ?Platform $platform = null;

    public static ?Region $region = null;

    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Where you play.')]
        Platform $platform,
        #[Option(description: 'Which region.')]
        ?Region $region = null,
    ): void {
        self::$platform = $platform;
        self::$region = $region;
    }
}
