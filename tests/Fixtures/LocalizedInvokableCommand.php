<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Greets someone', translationKey: 'commands.greet')]
final class LocalizedInvokableCommand
{
    public function __invoke(
        #[Option(description: 'Who to greet')] string $name,
    ): void {}
}
