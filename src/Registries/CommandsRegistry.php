<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\Outcome;
use Tempest\Container\Singleton;

/**
 * Holds every compiled command and wires it up: registering it with Discord and
 * binding its handlers to the interactions that come back.
 */
#[Singleton]
final class CommandsRegistry
{
    /** @var array<string, CommandDefinition> */
    private array $commands = [];

    public function __construct(
        public readonly AllCommandExtension $extension,
        private readonly CommandRegistrar $registrar,
        private readonly CommandDispatcher $dispatcher,
        private readonly AutocompleteResponder $autocomplete,
    ) {}

    public function add(CommandDefinition $command): void
    {
        $key = $command->key();

        $this->commands[$key] = isset($this->commands[$key])
            ? $this->commands[$key]->mergedWith($command)
            : $command;
    }

    /**
     * Pushes every command to Discord, reporting on each scope as it goes.
     *
     * @return list<Outcome>
     */
    public function register(Discord $discord): array
    {
        return $this->registrar->register($discord, $this->commands);
    }

    /**
     * Binds every handler to the interaction path it answers.
     *
     * @return list<Outcome>
     */
    public function listen(): array
    {
        $outcomes = [];

        foreach ($this->commands as $command) {
            foreach ($command->handlers as $handler) {
                $this->bind($handler, $outcomes);
            }
        }

        if ($outcomes === []) {
            return [Outcome::warning(
                'Listened 0 commands. Maybe this behavior is not expected, or you just did not created any command yet.',
            )];
        }

        return $outcomes;
    }

    /**
     * @param list<Outcome> $outcomes
     */
    private function bind(HandlerDefinition $handler, array &$outcomes): void
    {
        $this->extension->bind(
            command: $handler->path,
            listener: function (CommandInteraction $interaction) use ($handler): void {
                $this->dispatcher->dispatch($handler, $interaction);
            },
            autocomplete: function (CommandInteraction $interaction) use ($handler): void {
                $this->autocomplete->respond($handler, $interaction);
            },
        );

        $outcomes[] = Outcome::success('Command "' . $handler->path . '" listened.');
    }
}
