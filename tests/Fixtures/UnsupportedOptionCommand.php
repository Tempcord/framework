<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;

#[Command(description: 'Declares an option Discord has no type for')]
final class UnsupportedOptionCommand
{
    public function __invoke(
        #[Option(description: 'an array')] array $values,
    ): void {}
}
