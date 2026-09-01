<?php

namespace Tempcord\Tests\Fixtures;

use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Suggests from a service')]
final class InjectedSearchCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Which track', autocomplete: TrackAutocomplete::class)]
        string $track,
    ): void {}
}
