<?php

namespace Tempcord\Discord;

use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Definitions\OptionDefinition;
use Tempcord\Definitions\SubcommandDefinition;
use Tempcord\Definitions\SubcommandGroupDefinition;

/**
 * Turns compiled definitions into the payload Fenrir sends to Discord.
 *
 * Every dependency on Fenrir's builders lives here, so a change on their side
 * is a change to one file rather than to every attribute in the framework.
 */
final readonly class CommandBuilderFactory
{
    public function forCommand(CommandDefinition $command): CommandBuilder
    {
        $builder = CommandBuilder::new()
            ->setName($command->name)
            ->setNsfw($command->isNsfw)
            ->setDmPermission($command->directMessage)
            ->setType($command->type);

        if ($command->type === ApplicationCommandTypes::CHAT_INPUT) {
            // The compiler guarantees a description for chat input commands.
            $builder->setDescription((string) $command->description);
        }

        foreach ($command->options as $option) {
            $builder->addOption($this->forOption($option));
        }

        return $builder;
    }

    public function forOption(
        SubcommandGroupDefinition|SubcommandDefinition|OptionDefinition $option,
    ): CommandOptionBuilder {
        return match (true) {
            $option instanceof SubcommandGroupDefinition => $this->forGroup($option),
            $option instanceof SubcommandDefinition => $this->forSubcommand($option),
            default => $this->forParameter($option),
        };
    }

    private function forGroup(SubcommandGroupDefinition $group): CommandOptionBuilder
    {
        $builder = CommandOptionBuilder::new()
            ->setName($group->name)
            ->setDescription($group->description)
            ->setType(ApplicationCommandOptionType::SUB_COMMAND_GROUP);

        foreach ($group->subcommands as $subcommand) {
            $builder->addOption($this->forSubcommand($subcommand));
        }

        return $builder;
    }

    private function forSubcommand(SubcommandDefinition $subcommand): CommandOptionBuilder
    {
        $builder = CommandOptionBuilder::new()
            ->setName($subcommand->name)
            ->setDescription($subcommand->description)
            ->setType(ApplicationCommandOptionType::SUB_COMMAND);

        foreach ($subcommand->options as $option) {
            $builder->addOption($this->forParameter($option));
        }

        return $builder;
    }

    private function forParameter(OptionDefinition $option): CommandOptionBuilder
    {
        return CommandOptionBuilder::new()
            ->setName($option->name)
            ->setDescription($option->description)
            ->setRequired($option->isRequired)
            ->setType($option->type)
            ->setAutoComplete($option->hasAutocomplete());
    }
}
