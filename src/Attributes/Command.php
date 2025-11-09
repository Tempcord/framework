<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Exceptions\Rest\Helpers\Command\InvalidCommandNameException;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Tempcord\Traits\HasAttributes;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;
use function Tempest\Support\Arr\dot;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Command
{
    use HasAttributes;

    public ClassReflector $reflector;

    public string $name {
        get {
            if ($this->getAttribute('name')) {
                $name = $this->getAttribute('name');
                return $name instanceof BackedEnum ? $name->value : $name;
            }

            return str($this->reflector->getShortName())
                ->replaceEnd('Command', '')
                ->replaceStart('Command', '')
                ->snake('_')
                ->lower()
                ->toString();
        }
    }
    public $hasSubcommands {
        get {
            return count($this->subCommands) > 0;
        }
    }

    /**
     * Determine if command has subcommands.
     */
    public bool $isParentCommand {
        get {
            return array_any($this->reflector->getPublicMethods(), fn($method) => $method->hasAttribute(Subcommand::class));
        }
    }

    /**
     * If command is a "parent" command - it should contain subcommands
     */
    public array $subCommands {
        get {
            if (!$this->isParentCommand) {
                return [];
            }

            $subCommands = [];
            foreach ($this->reflector->getPublicMethods() as $method) {
                if ($method->hasAttribute(Subcommand::class)) {
                    /** @var Subcommand $subCommand */
                    $subCommand = $method->getAttribute(Subcommand::class);
                    $subCommand->reflector = $method;
                    $subCommands[$subCommand->name] = $subCommand;
                }
            }

            return $subCommands;
        }
    }


    public MethodReflector $handler {
        get {

            /**
             * Let determine how command handler is declared.
             *
             * We can declare command handler with a custom handler using HandledBy attribute.
             * We can declare command with Subcommands, so any Subcommand should have own handler, and command is not
             * registering own handler.
             *
             * By default, we assume that it's an invokable command
             */
            switch (true) {
                case $this->reflector->hasAttribute(HandledBy::class):
                    $handledBy = $this->reflector->getAttribute(HandledBy::class);
                    return $this->reflector->getMethod($handledBy->method);
                default:
                    return $this->reflector->getMethod('__invoke');
            }
        }
    }

    public bool $isGuildCommand {
        get {
            return isset($this->guildId);
        }
    }

//    public array $handlers {
//        get {
//            $keys[$this->name] = [];
//            foreach ($this->options as $option) {
//                if (in_array(get_class($option), [SubcommandGroup::class, Subcommand::class])) {
//                    $keys[$this->name][$option->name] = $option->key;
//                } else {
//                    $fakeSubcommand = new Subcommand(name: $option->name, description: $option->description);
//                    $fakeSubcommand->reflector = $this->reflector->getMethod('__invoke');
//                    $keys[$this->name] = $fakeSubcommand->key;
//                }
//            }
//            return dot($keys);
//        }
//    }
//
//    /** @var array<SubcommandGroup|Subcommand|Option> */
//    public array $options {
//        get {
//            $options = [];
//            /**
//             * Let determine how command is declared.
//             * We can declare command with a custom handler using HandledBy attribute
//             * We can declare command with SubcommandGroup attribute
//             * We can declare command as invokable class
//             */
//            switch (true) {
//                case $this->reflector->hasAttribute(HandledBy::class):
//                    $handledBy = $this->reflector->getAttribute(HandledBy::class);
//                    $subcommand = new Subcommand(
//                        name: $this->reflector::class,
//                        description: $this->reflector::class,
//                    );
//                    $subcommand->reflector = $this->reflector->getMethod($handledBy->method);
//                    foreach ($subcommand->options as $option) {
//                        $options[$option->name] = $option;
//                    }
//                    break;
//                case $this->reflector->hasAttribute(SubcommandGroup::class):
//                    $subcommandGroup = $this->reflector->getAttribute(SubcommandGroup::class);
//                    $subcommandGroup->reflector = $this->reflector;
//                    $options[$subcommandGroup->name] = $subcommandGroup;
//                    break;
//                default:
//                    $fakeSubcommandGroup = new SubcommandGroup(name: 'fake', description: 'fake');
//                    $fakeSubcommandGroup->reflector = $this->reflector;
//                    foreach ($fakeSubcommandGroup->options as $option) {
//                        $options[$option->name] = $option;
//                    }
//
//                    if (empty($options)) {
//
//                        /*
//                         * We assume that there is no Subcommands found and this is an invokable command
//                         * User should provide the __invoke command, so we actually can read the method options (if there are some)
//                         */
//                        if (!$this->reflector->getReflection()->hasMethod('__invoke')) {
//                            throw new RuntimeException('Class [' . $this->reflector->getName() . '] should declare public sub-commands or have an __invoke method');
//                        }
//
//                        $fakeSubcommand = new Subcommand(name: 'fake', description: 'fake');
//                        $fakeSubcommand->reflector = $this->reflector->getMethod('__invoke');
//                        foreach ($fakeSubcommand->options as $option) {
//                            $options[$option->name] = $option;
//                        }
//                    }
//            }
//
//            return $options;
//        }
//    }


    public function __construct(
        string|BackedEnum|null         $name = null,
        public ?string                 $description = null,
        public ?int                    $guildId = null,
        public bool                    $isNsfw = false,
        public array                   $permissions = [],
        public bool                    $directMessage = true,
        public ApplicationCommandTypes $type = ApplicationCommandTypes::CHAT_INPUT
    )
    {
        $this->setAttribute('name', $name);

        if (($this->type === ApplicationCommandTypes::CHAT_INPUT) && !$this->description) {
            throw new \InvalidArgumentException("Description for command is required when type=CHAT_INPUT");
        }
    }

    /**
     * @throws InvalidCommandNameException
     */
    public function build(ClassReflector $class): CommandBuilder
    {
        $this->reflector = $class;

        $command = CommandBuilder::new()
            ->setName($this->name)
            ->setNsfw($this->isNsfw)
            ->setDmPermission($this->directMessage)
            ->setType($this->type);

        if ($this->type === ApplicationCommandTypes::CHAT_INPUT) {

//            if (!$this->description) {
//                throw new \LogicException("Description for command [$this->command] is required when type=CHAT_INPUT");
//            }

            $command->setDescription($this->description ?? '');
        }


//        foreach ($this->options as $option) {
//            $command->addOption($option->build);
//        }


        return $command;
    }
}