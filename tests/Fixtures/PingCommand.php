<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Replies with pong')]
final class PingCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Who to greet')] string $name,
        #[Option(description: 'How many times')] int $times = 1,
    ): string {
        return $name . ':' . $times;
    }
}
