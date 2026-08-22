<?php

namespace Tempcord\Registries;

use Tempcord\Definitions\CommandDefinition;
use Tempest\Container\Singleton;

/**
 * Holds every compiled command.
 *
 * Storage only, and deliberately so: discovery constructs whatever it depends
 * on while the container is still being assembled, before the initializers that
 * provide services like the logger have themselves been discovered. Anything
 * needing those belongs in the runtime, which is built later.
 */
#[Singleton]
final class CommandsRegistry
{
    /** @var array<string, CommandDefinition> */
    private array $commands = [];

    public function add(CommandDefinition $command): void
    {
        $key = $command->key();

        $this->commands[$key] = isset($this->commands[$key])
            ? $this->commands[$key]->mergedWith($command)
            : $command;
    }

    /**
     * @return array<string, CommandDefinition>
     */
    public function all(): array
    {
        return $this->commands;
    }

    public function count(): int
    {
        return count($this->commands);
    }
}
