<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Discord\Interaction\CommandInteraction;

#[Command(description: 'Options named after camelCase parameters')]
final class CamelCasedOptionsCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Taken from the parameter')]
        ?string $carePackage = null,
        #[Option(description: 'Several words')]
        ?string $howManyTimes = null,
        #[Option(name: 'kept_as_written', description: 'An explicit name is the author\'s')]
        ?string $somethingElse = null,
        #[Option(description: 'Already lower case')]
        ?string $platform = null,
    ): void {}
}
