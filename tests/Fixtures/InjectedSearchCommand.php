<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;

#[Command(description: 'Suggests from a service')]
final class InjectedSearchCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Which track', autocomplete: TrackAutocomplete::class)]
        string $track,
    ): void {}
}
