<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Subcommand;
use Tempcord\Declare\Command\SubcommandGroup;

#[Command(description: 'Guarded through a group', middleware: [OuterMiddleware::class])]
#[SubcommandGroup(name: 'keys', description: 'Keys', middleware: [InnerMiddleware::class])]
final class GuardedGroupCommand
{
    #[Subcommand(name: 'cut', description: 'Cut a key', middleware: [RefusingMiddleware::class])]
    public function cut(): void {}
}
