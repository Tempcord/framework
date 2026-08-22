<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\Outcome;
use Tempest\Container\Singleton;
use Throwable;
use function React\Async\await;

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
        private readonly CommandBuilderFactory $builders,
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
     * Pushes every command to Discord, reporting on each as it goes.
     *
     * @return list<Outcome>
     */
    public function register(Discord $discord): array
    {
        if ($this->commands === []) {
            return [Outcome::warning('No commands to register.')];
        }

        try {
            $application = await($discord->rest->application->getCurrent());
        } catch (Throwable $throwable) {
            return [Outcome::error($throwable->getMessage())];
        }

        $outcomes = [];

        foreach ($this->commands as $command) {
            try {
                /*
                 * Guild commands go to a different endpoint that additionally
                 * takes the guild id, so the two cannot share a call.
                 */
                await($command->isGlobal()
                    ? $discord->rest->globalCommand->createApplicationCommand(
                        $application->id,
                        $this->builders->forCommand($command),
                    )
                    : $discord->rest->guildCommand->createApplicationCommand(
                        $application->id,
                        $command->guildId,
                        $this->builders->forCommand($command),
                    ));

                $outcomes[] = Outcome::success($command->isGlobal()
                    ? 'Command "' . $command->name . '" registered globally.'
                    : 'Command "' . $command->name . '" registered in guild ' . $command->guildId . '.');
            } catch (Throwable $throwable) {
                $outcomes[] = Outcome::error(
                    'Command "' . $command->name . '": ' . $throwable->getMessage(),
                );
            }
        }

        return $outcomes;
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
