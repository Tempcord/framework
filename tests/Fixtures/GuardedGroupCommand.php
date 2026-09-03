<?php

namespace Tempcord\Tests\Fixtures;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;

#[Command(description: 'Guarded through a group', middleware: [OuterMiddleware::class])]
#[SubcommandGroup(name: 'keys', description: 'Keys', middleware: [InnerMiddleware::class])]
final class GuardedGroupCommand
{
    #[Subcommand(name: 'cut', description: 'Cut a key', middleware: [RefusingMiddleware::class])]
    public function cut(): void {}
}
