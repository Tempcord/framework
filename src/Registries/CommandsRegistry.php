<?php

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\ApplicationCommandOptionChoice;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;
use Tempcord\Interfaces\Autocomplete;
use Tempcord\AllCommandExtension;
use Tempcord\InteractionCallbackBuilder;
use Tempest\Console\Console;
use Tempest\Container\Singleton;
use Throwable;
use function React\Async\await;

#[Singleton]
final class CommandsRegistry
{
    /**
     * Discord rejects an autocomplete response carrying more choices than this.
     */
    private const int MAX_CHOICES = 25;

    /** @var array<Command> */
    private array $commands = [];

    public function __construct(
        public readonly AllCommandExtension $extension
    ) {}

    public function add(Command $command): void
    {
        $key = self::key($command);

        if (array_key_exists($key, $this->commands)) {
            $command->mergeOptions($this->commands[$key]);
        }

        $this->commands[$key] = $command;
    }

    /**
     * Commands are keyed by name, scoped by guild so that a guild command
     * neither collides with another command in the same guild nor with the
     * global command of the same name.
     *
     * Discord command names cannot contain ":", so the two key shapes
     * can never overlap.
     */
    private static function key(Command $command): string
    {
        return $command->guildId === null
            ? $command->name
            : $command->guildId . ':' . $command->name;
    }

    /**
     * @throws Throwable
     */
    public function register(Console $console, Discord $discord): void
    {
        if (empty($this->commands)) {
            $console->warning('No commands to register.');
            return;
        }

        try {
            $application = await($discord->rest->application->getCurrent());
        } catch (Throwable $throwable) {
            $console->error($throwable->getMessage());
            return;
        }

        foreach ($this->commands as $command) {
            try {
                /*
                 * Guild commands go to a different endpoint that additionally
                 * takes the guild id, so the two cannot share a call.
                 */
                await($command->guildId === null
                    ? $discord->rest->globalCommand->createApplicationCommand(
                        $application->id,
                        $command->build,
                    )
                    : $discord->rest->guildCommand->createApplicationCommand(
                        $application->id,
                        $command->guildId,
                        $command->build,
                    ));

                $console->success($command->guildId === null
                    ? 'Command "' . $command->name . '" registered globally.'
                    : 'Command "' . $command->name . '" registered in guild ' . $command->guildId . '.');
            } catch (Throwable $throwable) {
                $console->error('Command "' . $command->name . '": ' . $throwable->getMessage());
            }
        }
    }

    public function listen(Console $console): void
    {
        $console->info('Starting Commands');

        $count = 0;

        foreach ($this->commands as $command) {
            foreach ($command->handlers as $key => $handler) {

                $this->extension->bind(
                    command: $key,
                    listener: function (CommandInteraction $interaction) use ($console, $handler) {
                        try {
                            $handler($interaction);
                        } catch (\Throwable $e) {
                            $console->error($e->getMessage());
                        }
                    },
                    autocomplete: function (CommandInteraction $interaction) use ($command) {
                        $resolved = $this->resolveFocusedAndParam(
                            $interaction->interaction->data->options ?? [],
                            $command,
                        );

                        // Nothing focused, or focused on an option this command does not declare.
                        if ($resolved === null) {
                            return null;
                        }

                        [$option, $interactionOption] = $resolved;

                        if (!$option->autocomplete instanceof Autocomplete) {
                            return null;
                        }

                        $interaction->createInteractionResponse(
                            InteractionCallbackBuilder::new()
                                ->setChoices($this->toChoices(
                                    $option->autocomplete->handle($interaction, $interactionOption->value),
                                ))
                                ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT)
                        );

                        return null;
                    }
                );

                $count++;

                $console->success('Command "' . $key . '" listened.');
            }
        }

        if ($count <= 0) {
            $console->warning('Listened ' . $count . ' commands. Maybe this behavior is not expected, or you just did not created any command yet.');
        }
    }

    /**
     * Normalises whatever an Autocomplete returned into the choice list Discord
     * accepts.
     *
     * A bare scalar stands for a single suggestion. A list uses each entry as
     * its own label; a map uses its keys as labels. Choices built by hand are
     * passed through untouched.
     *
     * @return list<ApplicationCommandOptionChoice>
     */
    private function toChoices(mixed $value): array
    {
        $choices = is_array($value) ? $value : [$value];
        $choices = array_slice($choices, 0, self::MAX_CHOICES, preserve_keys: true);

        $isList = array_is_list($choices);

        return array_map(
            static function (mixed $choice, int|string $label) use ($isList): ApplicationCommandOptionChoice {
                if ($choice instanceof ApplicationCommandOptionChoice) {
                    return $choice;
                }

                $applicationCommandOptionChoice = new ApplicationCommandOptionChoice();
                $applicationCommandOptionChoice->name = (string) ($isList ? $choice : $label);
                $applicationCommandOptionChoice->value = $choice;

                return $applicationCommandOptionChoice;
            },
            $choices,
            array_keys($choices),
        );
    }

    /**
     * @param array $interactionOptions — array of ApplicationCommandInteractionDataOptionStructure
     * @param Command|SubcommandGroup|Subcommand $definition — Tempcord definition
     * @return array{ Option, ApplicationCommandInteractionDataOptionStructure }|null
     */
    private function resolveFocusedAndParam(array $interactionOptions, Command|SubcommandGroup|Subcommand $definition): ?array
    {
        /** @var ApplicationCommandInteractionDataOptionStructure $option */
        foreach ($interactionOptions as $option) {
            $type = $option->type->value;
            $name = $option->name;

            // If SUB_COMMAND_GROUP or SUB_COMMAND, go deeper
            if (in_array($type, [1, 2], true)) {
                // $definition->options[$name] should be the nested command/group
                $nextDefinition = $definition->options[$name] ?? null;

                if ($nextDefinition && !empty($option->options)) {
                    $result = $this->resolveFocusedAndParam($option->options, $nextDefinition);
                    if ($result !== null) {
                        return $result;
                    }
                }
            } else if ((isset($option->focused) && $option->focused === true) && isset($definition->options[$name])) {
                return [
                    $definition->options[$name],
                    $option
                ];
            }
        }

        return null;
    }
}
