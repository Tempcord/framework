<?php

namespace Tempcord;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\FilteredEventEmitter;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use function Freezemage\ArrayUtils\find;

final class AllCommandExtension extends \Ragnarok\Fenrir\Command\AllCommandExtension
{

    public function initialize(Discord $discord): void
    {
        $this->commandListener = new FilteredEventEmitter(
            $discord->gateway->events,
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interactionCreate) => isset($interactionCreate->type)
                && ($interactionCreate->type === InteractionType::APPLICATION_COMMAND || $interactionCreate->type === InteractionType::APPLICATION_COMMAND_AUTOCOMPLETE)
                && $this->emitInteraction($interactionCreate)
        );

        $this->commandListener->on(Events::INTERACTION_CREATE, function (InteractionCreate $interaction) use ($discord) {

            if ($interaction->type === InteractionType::APPLICATION_COMMAND) {
                $this->handleInteraction($interaction, $discord);
                return null;
            }

            if ($interaction->type === InteractionType::APPLICATION_COMMAND_AUTOCOMPLETE) {
                $this->handleInteractionAutocomplete($interaction, $discord);
                return null;
            }

        });

        $this->commandListener->start();
    }

    public function bind(string $command, callable $listener, callable $autocomplete): void
    {
        $this->on($command, $listener);
        $this->on($command . '.autocomplete', $autocomplete);
    }


    private function handleInteraction(InteractionCreate $interaction, Discord $discord): void
    {
        $commandName = $this->getFullNameByInteraction($interaction);
        $firedCommand = new CommandInteraction($interaction, $discord);

        $this->emit($commandName, [$firedCommand]);
    }

    private function handleInteractionAutocomplete(InteractionCreate $interaction, Discord $discord): void
    {
        $commandName = $this->getFullNameByInteraction($interaction);
        $firedCommand = new CommandInteraction($interaction, $discord);

        $this->emit($commandName . '.autocomplete', [$firedCommand]);
    }


    protected function getFullNameByInteraction(InteractionCreate $command): string
    {
        $names = [$command->data->name];

        $this->drillName($command->data->options ?? [], $names);

        return implode('.', $names);
    }

    private function drillName(array $options, array &$names): void
    {
        /** @var ?ApplicationCommandInteractionDataOptionStructure $subCommand */
        $subCommand = find($options ?? [], function (ApplicationCommandInteractionDataOptionStructure $option) {
            return in_array($option->type, [
                ApplicationCommandOptionType::SUB_COMMAND,
                ApplicationCommandOptionType::SUB_COMMAND_GROUP,
            ], true);
        });

        if (!is_null($subCommand)) {
            $names[] = $subCommand->name;

            $this->drillName($subCommand->options ?? [], $names);
        }
    }


}