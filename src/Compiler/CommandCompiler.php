<?php

namespace Tempcord\Compiler;

use BackedEnum;
use LogicException;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Parts\Role;
use Ragnarok\Fenrir\Parts\User;
use RuntimeException;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;
use Tempcord\Definitions\CommandDefinition;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Definitions\OptionDefinition;
use Tempcord\Definitions\SubcommandDefinition;
use Tempcord\Definitions\SubcommandGroupDefinition;
use Tempcord\Localization\LocalizationProvider;
use Tempcord\Localization\NullLocalizations;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;
use Tempest\Reflection\ParameterReflector;
use function Tempest\Support\str;

/**
 * Turns the attributes a user wrote into an immutable CommandDefinition.
 *
 * All reflection happens here, once, at discovery time. Attributes stay inert
 * declarations and nothing downstream has to walk a class again.
 */
final readonly class CommandCompiler
{
    public function __construct(
        private LocalizationProvider $localizations = new NullLocalizations(),
    ) {}

    /**
     * PHP parameter types the framework knows how to ask Discord for.
     */
    private const array OPTION_TYPES = [
        'string' => ApplicationCommandOptionType::STRING,
        'int' => ApplicationCommandOptionType::INTEGER,
        'float' => ApplicationCommandOptionType::NUMBER,
        'bool' => ApplicationCommandOptionType::BOOLEAN,
        User::class => ApplicationCommandOptionType::USER,
        Channel::class => ApplicationCommandOptionType::CHANNEL,
        Role::class => ApplicationCommandOptionType::ROLE,
    ];

    public function compile(ClassReflector $class, Command $command): CommandDefinition
    {
        $name = $this->nameOf($class, $command);
        $options = [];
        $handlers = [];

        $key = $command->translationKey;
        $group = $this->groupOf($class, $key);
        $subcommands = $this->subcommandsOf($class, $key);

        if ($group !== null) {
            $options[$group->name] = $group;

            foreach ($group->subcommands as $subcommand) {
                $path = $name . '.' . $group->name . '.' . $subcommand->name;

                $handlers[$path] = new HandlerDefinition(
                    path: $path,
                    method: $subcommand->method,
                    options: $subcommand->options,
                    optionPath: $group->name . '.' . $subcommand->name,
                );
            }
        } elseif ($subcommands !== []) {
            foreach ($subcommands as $subcommand) {
                $options[$subcommand->name] = $subcommand;
                $path = $name . '.' . $subcommand->name;

                $handlers[$path] = new HandlerDefinition(
                    path: $path,
                    method: $subcommand->method,
                    options: $subcommand->options,
                    optionPath: $subcommand->name,
                );
            }
        } else {
            /*
             * No subcommands anywhere means __invoke is the command, and its
             * parameters are the command's options.
             */
            $invoke = $this->invokerOf($class);
            $options = $this->optionsOf($invoke, $key);

            $handlers[$name] = new HandlerDefinition(
                path: $name,
                method: $invoke,
                options: $options,
            );
        }

        if ($command->type === ApplicationCommandTypes::CHAT_INPUT && $command->description === null) {
            throw new LogicException("Description for command [{$name}] is required when type=CHAT_INPUT");
        }

        return new CommandDefinition(
            name: $name,
            description: $command->description,
            guildId: $command->guildId,
            isNsfw: $command->isNsfw,
            directMessage: $command->directMessage,
            type: $command->type,
            permissions: $command->permissions,
            options: $options,
            handlers: $handlers,
            nameLocalizations: $this->translate($key, 'name'),
            descriptionLocalizations: $this->translate($key, 'description'),
        );
    }

    /**
     * An explicit name wins; otherwise the class name carries it, with the
     * conventional Command prefix or suffix stripped and the rest snake_cased.
     */
    private function nameOf(ClassReflector $class, Command $command): string
    {
        if ($command->name !== null) {
            return $command->name instanceof BackedEnum
                ? (string) $command->name->value
                : $command->name;
        }

        return str($class->getShortName())
            ->replaceEnd('Command', '')
            ->replaceStart('Command', '')
            ->snake('_')
            ->lower()
            ->toString();
    }

    private function groupOf(ClassReflector $class, ?string $key): ?SubcommandGroupDefinition
    {
        if (!$class->hasAttribute(SubcommandGroup::class)) {
            return null;
        }

        /** @var SubcommandGroup $group */
        $group = $class->getAttribute(SubcommandGroup::class);
        $name = $this->valueOf($group->name);
        $groupKey = $this->nest($key, $name);

        return new SubcommandGroupDefinition(
            name: $name,
            description: $group->description,
            subcommands: $this->subcommandsOf($class, $groupKey),
            nameLocalizations: $this->translate($groupKey, 'name'),
            descriptionLocalizations: $this->translate($groupKey, 'description'),
        );
    }

    /**
     * @return array<string, SubcommandDefinition>
     */
    private function subcommandsOf(ClassReflector $class, ?string $key): array
    {
        $subcommands = [];

        foreach ($class->getPublicMethods() as $method) {
            if (!$method->hasAttribute(Subcommand::class)) {
                continue;
            }

            /** @var Subcommand $subcommand */
            $subcommand = $method->getAttribute(Subcommand::class);
            $name = $this->valueOf($subcommand->name);

            $subcommandKey = $this->nest($key, $name);

            $subcommands[$name] = new SubcommandDefinition(
                name: $name,
                description: $subcommand->description,
                options: $this->optionsOf($method, $subcommandKey),
                method: $method,
                nameLocalizations: $this->translate($subcommandKey, 'name'),
                descriptionLocalizations: $this->translate($subcommandKey, 'description'),
            );
        }

        return $subcommands;
    }

    private function invokerOf(ClassReflector $class): MethodReflector
    {
        if (!$class->getReflection()->hasMethod('__invoke')) {
            throw new RuntimeException(
                'Class [' . $class->getName() . '] should declare public sub-commands or have an __invoke method',
            );
        }

        return $class->getMethod('__invoke');
    }

    /**
     * @return array<string, OptionDefinition>
     */
    private function optionsOf(MethodReflector $method, ?string $key): array
    {
        $options = [];

        foreach ($method->getParameters() as $parameter) {
            if (!$parameter->hasAttribute(Option::class)) {
                continue;
            }

            /** @var Option $option */
            $option = $parameter->getAttribute(Option::class);
            $name = $option->name ?? $parameter->getName();

            $optionKey = $this->nest($key, $name);

            $options[$name] = new OptionDefinition(
                name: $name,
                description: $option->description,
                type: $this->typeOf($parameter),
                isRequired: !$parameter->isOptional(),
                autocomplete: $option->autocomplete,
                parameter: $parameter,
                choices: $this->choicesOf($option),
                minValue: $option->minValue,
                maxValue: $option->maxValue,
                minLength: $option->minLength,
                maxLength: $option->maxLength,
                channelTypes: $option->channelTypes,
                nameLocalizations: $this->translate($optionKey, 'name'),
                descriptionLocalizations: $this->translate($optionKey, 'description'),
            );
        }

        return $options;
    }

    /**
     * A list of choices labels each entry with itself; a map uses its keys as
     * the labels, matching how ArrayAutocomplete already reads its items.
     *
     * @return array<string, string|int|float>
     */
    private function choicesOf(Option $option): array
    {
        if ($option->choices === []) {
            return [];
        }

        if (!array_is_list($option->choices)) {
            return $option->choices;
        }

        $choices = [];

        foreach ($option->choices as $choice) {
            $choices[(string) $choice] = $choice;
        }

        return $choices;
    }

    private function typeOf(ParameterReflector $parameter): ApplicationCommandOptionType
    {
        if (!$parameter->getReflection()->hasType()) {
            throw new LogicException('Command option does not have type');
        }

        return self::OPTION_TYPES[$parameter->getType()->getName()]
            ?? throw new LogicException('Command option type not supported');
    }

    /**
     * Extends a translation key one step down the command tree. Null stays
     * null, so a command that declares no key localizes nothing.
     */
    private function nest(?string $key, string $segment): ?string
    {
        return $key === null ? null : $key . '.' . $segment;
    }

    /**
     * @return array<string, string>
     */
    private function translate(?string $key, string $field): array
    {
        return $key === null ? [] : $this->localizations->forKey($key . '.' . $field);
    }

    private function valueOf(string|BackedEnum $name): string
    {
        return $name instanceof BackedEnum ? (string) $name->value : $name;
    }
}
