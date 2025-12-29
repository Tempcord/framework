<?php

namespace Tempcord\ConsoleCommands;

use Tempcord\Support\Traits\HasTempcord;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;

final readonly class CommandsRegisterCommand
{
    use HasConsole, HasTempcord;

    #[ConsoleCommand(name: 'commands:register', description: 'Register all commands')]
    public function __invoke(): void
    {
        $commands = $this->tempcord->commandsRegistry->bucket->items;
        $this->console->header(sprintf('Registered %d commands', count($commands)));
    }
}
