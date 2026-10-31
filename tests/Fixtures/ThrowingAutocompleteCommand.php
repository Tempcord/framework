<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Declare\Command\Autocomplete;
use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;

#[Command(description: 'Its suggestions do not work')]
final class ThrowingAutocompleteCommand
{
    public function __invoke(
        #[Option(description: 'What to look for')] string $query = '',
    ): void {}

    #[Autocomplete(option: 'query')]
    public function completeQuery(string $typed): array
    {
        throw new RuntimeException('no suggestions today');
    }
}
