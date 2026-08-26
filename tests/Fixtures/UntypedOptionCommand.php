<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Declares an option with no PHP type to read')]
final class UntypedOptionCommand
{
    public function __invoke(
        #[Option(description: 'no type')] $whatever,
    ): void {}
}
