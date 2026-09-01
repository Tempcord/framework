<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Interfaces\Autocomplete;

/**
 * An autocomplete with a dependency, which only works because it is named by
 * class and built by the container.
 */
final readonly class TrackAutocomplete implements Autocomplete
{
    public function __construct(
        private TrackRepository $tracks,
    ) {}

    public function handle(CommandInteraction $interaction, mixed $value): array
    {
        return $this->tracks->matching((string) $value);
    }
}
