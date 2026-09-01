<?php

namespace Tempcord\Tests\Fixtures;

use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Autocomplete;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

/**
 * A command that suggests its own values, using the dependencies it already has.
 */
#[Command(description: 'Suggests its own values')]
final readonly class SelfCompletingCommand
{
    public function __construct(
        private TrackRepository $tracks,
    ) {}

    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Which track')] string $track,
        #[Option(description: 'Which mood')] string $mood = '',
    ): void {}

    /**
     * @return list<string>
     */
    #[Autocomplete(option: 'track')]
    public function completeTrack(string $typed): array
    {
        return $this->tracks->matching($typed);
    }

    /**
     * Takes the interaction too, in the other order, to show the arguments are
     * matched by what they are rather than by position.
     *
     * @return list<string>
     */
    #[Autocomplete(option: 'mood')]
    public function completeMood(CommandInteraction $interaction, mixed $typed): array
    {
        return [$interaction->interaction->id, (string) $typed];
    }
}
