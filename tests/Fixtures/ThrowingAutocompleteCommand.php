<?php

namespace Tempcord\Tests\Fixtures;

use RuntimeException;
use Tempcord\Attributes\Autocomplete;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

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
