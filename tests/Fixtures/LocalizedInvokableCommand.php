<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;

#[Command(description: 'Greets someone', translationKey: 'commands.greet')]
final class LocalizedInvokableCommand
{
    public function __invoke(
        #[Option(description: 'Who to greet')] string $name,
    ): void {}
}
