<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Points at a class that cannot suggest anything')]
final class NotAnAutocompleteCommand
{
    public function __invoke(
        #[Option(description: 'What to look for', autocomplete: TrackRepository::class)]
        string $query = '',
    ): void {}
}
