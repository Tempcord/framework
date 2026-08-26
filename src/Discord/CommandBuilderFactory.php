<?php

namespace Tempcord\Discord;

use CyberWolf\Discord\Bitwise\Bitwise;
use CyberWolf\Discord\Enums\ApplicationCommandOptionType;
use CyberWolf\Discord\Enums\ApplicationCommandTypes;
use CyberWolf\Discord\Rest\Helpers\Command\CommandBuilder;
use CyberWolf\Discord\Rest\Helpers\Command\CommandOptionBuilder;
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
    /**
     * The payload for one command, ready to send.
     *
     * Default permissions are written here rather than through the builder's
     * setDefaultMemberPermissions, which sends the binary representation of the
     * bit field where Discord expects the decimal one. Asking for ADMINISTRATOR
     * that way grants five permissions nobody asked for.
     *
     * @see https://github.com/dc-Ragnarok/Fenrir/pull/134
     *
     * @return array<string, mixed>
     */
    public function payloadFor(CommandDefinition $command): array
    {
        $payload = $this->forCommand($command)->get();

        if ($command->permissions !== []) {
            $payload['default_member_permissions'] = (string) Bitwise::from(...$command->permissions)->get();
        }

        return $payload;
    }

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

            if ($command->descriptionLocalizations !== []) {
                $builder->setDescriptionLocalizations($command->descriptionLocalizations);
            }
        }

        if ($command->nameLocalizations !== []) {
            $builder->setNameLocalizations($command->nameLocalizations);
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
        $builder = $this->localize(
            CommandOptionBuilder::new()
                ->setName($group->name)
                ->setDescription($group->description)
                ->setType(ApplicationCommandOptionType::SUB_COMMAND_GROUP),
            $group->nameLocalizations,
            $group->descriptionLocalizations,
        );

        foreach ($group->subcommands as $subcommand) {
            $builder->addOption($this->forSubcommand($subcommand));
        }

        return $builder;
    }

    private function forSubcommand(SubcommandDefinition $subcommand): CommandOptionBuilder
    {
        $builder = $this->localize(
            CommandOptionBuilder::new()
                ->setName($subcommand->name)
                ->setDescription($subcommand->description)
                ->setType(ApplicationCommandOptionType::SUB_COMMAND),
            $subcommand->nameLocalizations,
            $subcommand->descriptionLocalizations,
        );

        foreach ($subcommand->options as $option) {
            $builder->addOption($this->forParameter($option));
        }

        return $builder;
    }

    /**
     * Localizations are left off entirely when there are none, rather than sent
     * as empty maps.
     *
     * @param array<string, string> $nameLocalizations
     * @param array<string, string> $descriptionLocalizations
     */
    private function localize(
        CommandOptionBuilder $builder,
        array $nameLocalizations,
        array $descriptionLocalizations,
    ): CommandOptionBuilder {
        if ($nameLocalizations !== []) {
            $builder->setNameLocalizations($nameLocalizations);
        }

        if ($descriptionLocalizations !== []) {
            $builder->setDescriptionLocalizations($descriptionLocalizations);
        }

        return $builder;
    }

    private function forParameter(OptionDefinition $option): CommandOptionBuilder
    {
        $builder = $this->localize(
            CommandOptionBuilder::new()
                ->setName($option->name)
                ->setDescription($option->description)
                ->setRequired($option->isRequired)
                ->setType($option->type)
                ->setAutoComplete($option->hasAutocomplete()),
            $option->nameLocalizations,
            $option->descriptionLocalizations,
        );

        foreach ($option->choices as $label => $value) {
            $builder->addChoice($label, $value);
        }

        if (!is_null($option->minValue)) {
            $builder->setMinValue($option->minValue);
        }

        if (!is_null($option->maxValue)) {
            $builder->setMaxValue($option->maxValue);
        }

        if (!is_null($option->minLength)) {
            $builder->setMinLength($option->minLength);
        }

        if (!is_null($option->maxLength)) {
            $builder->setMaxLength($option->maxLength);
        }

        if ($option->channelTypes !== []) {
            $builder->setChannelTypes(...$option->channelTypes);
        }

        return $builder;
    }
}
