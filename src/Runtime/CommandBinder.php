<?php

namespace Tempcord\Runtime;

use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Discord\AllCommandExtension;

/**
 * Binds each compiled handler to the interaction path it answers.
 */
final readonly class CommandBinder
{
    public function __construct(
        public AllCommandExtension $extension,
        private CommandDispatcher $dispatcher,
        private AutocompleteResponder $autocomplete,
    ) {}

    /**
     * @param array<string, CommandDefinition> $commands
     *
     * @return list<Outcome>
     */
    public function bindAll(array $commands): array
    {
        $outcomes = [];

        foreach ($commands as $command) {
            foreach ($command->handlers as $handler) {
                $this->bind($handler);

                $outcomes[] = Outcome::success('Command "' . $handler->path . '" listened.');
            }
        }

        if ($outcomes === []) {
            return [Outcome::warning(
                'Listened 0 commands. Maybe this behavior is not expected, or you just did not created any command yet.',
            )];
        }

        return $outcomes;
    }

    private function bind(HandlerDefinition $handler): void
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
    }
}
