<?php

namespace Tempcord\ConsoleCommands\Commands;

use Tempcord\Registries\CommandsRegistry;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;

final readonly class ListCommand
{
    public function __construct(
        private Console          $console,
        private CommandsRegistry $registry
    )
    {
    }

    #[ConsoleCommand(name: 'commands:list', description: 'List all bot commands')]
    public function __invoke(): void
    {
        foreach ($this->registry->bucket->items as $item) {
            $this->console->keyValue($item->command->name, $item->command->type->name);
        }
    }
}
