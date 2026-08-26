<?php

namespace Tempcord\Tests\Fixtures;

use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\AutoCompletes\ArrayAutocomplete;

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
