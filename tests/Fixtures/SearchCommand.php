<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;
use Tempcord\Support\Autocomplete\ArrayAutocomplete;

#[Command(description: 'Suggests as you type')]
final class SearchCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'What to look for', autocomplete: new ArrayAutocomplete(['alpha', 'beta', 'gamma']))]
        string $query,
        #[Option(description: 'Plain option with no suggestions')] string $note = '',
    ): void {}
}
