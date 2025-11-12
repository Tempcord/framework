<?php

namespace Tempcord\Attributes\Commands;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Tempcord\Attributes\HandledBy;
use Tempcord\Support\Commands\CommandHandler;
use Tempcord\Traits\HasAttributes;
use Tempest\Reflection\ClassReflector;
use Tempest\Support\Str\ImmutableString;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_CLASS)]
final class Command
{
    use HasAttributes;

    public ClassReflector $reflector;

    /**
     * Command name
     *
     * If none is provided from constructor - name would be created from class name,
     * removing "Command" from beginning and end of the class name.
     * Additionally, it will be snake_case.
     */
    public string $name {
        get {
            return $this->rememberAttribute('name', fn() => str($this->reflector->getShortName())
                ->replaceEnd('Command', '')
                ->replaceStart('Command', '')
                ->snake('_')
                ->lower()
                ->toString());
        }
    }

    /**
     * Is Command marked as grouped
     *
     * Checks if command has SubcommandGroup attribute
     */
    public bool $isGrouped {
        get {
            return $this->rememberAttribute('isGrouped', $this->reflector->hasAttribute(SubcommandGroup::class));
        }
    }

    /**
     * List of commands subcommands if there are any
     *
     * @var array<Subcommand>
     */
    public array $subCommands {
        get {
            $subCommands = [];
            foreach ($this->reflector->getPublicMethods() as $method) {
                if ($method->hasAttribute(Subcommand::class)) {
                    /** @var Subcommand $subCommand */
                    $subCommand = $method->getAttribute(Subcommand::class);
                    $subCommand->reflector = $method;
                    $subCommands[str($subCommand->name)->when(
                        $this->isGrouped,
                        fn(ImmutableString $name) => $name->prepend($this->reflector->getAttribute(SubcommandGroup::class)->name . ':')
                    )->toString()] = $subCommand;
                }
            }

            return $subCommands;
        }
    }

    /**
     * Command's handler
     *
     * Finds any kind of command handler.
     * Command handler could be defined inside HandledBy attribute
     * Command handler could be defined as only 1 public method of the command class
     * Command handler could be defined as invokable method
     *
     * If none is provided - route::defaultHandler method would be passed
     * @var CommandHandler
     */
    public CommandHandler $handler {
        get {

            $handler = new CommandHandler($this);

            if ($handledBy = $this->reflector->getAttribute(HandledBy::class)) {
                $handler->method = $this->reflector->getMethod($handledBy->method);
            }

            //Class contains only 1 method - then it's handler
            if (count($this->reflector->getPublicMethods()) === 1) {
                $handler->method = $this->reflector->getPublicMethods()[0];
            }

            //Is that class invokable
            if ($this->reflector->getReflection()->hasMethod('__invoke')) {
                $handler->method = $this->reflector->getMethod('__invoke');
            }

            if (count($this->subCommands)) {
                /**
                 * Default handler.
                 *
                 * Actually we are just making sure that Command with Subcommands would be handled property.
                 * If command has no subcommands - this handler would throw exception, informing that we do not
                 * know how to handle this command, and user should define own command handler.
                 */
                $handler->method = new ClassReflector(new class {

                    public function __invoke(CommandInteraction $interaction)
                    {

                    }

                })->getMethod('__invoke');
            }

            if (!$handler->method) {
                throw new \InvalidArgumentException('Command must have handler method.');
            }

            return $handler;
        }
    }

    /**
     * Build command options
     *
     * Options should include any handler options defined by user
     * If command is grouped - then we return only SubcommandGroups.
     * If command has subcommands and is not grouped - then we return all subcommands
     *
     * Options can be merged (if another command with the same base name is defined in another class)
     *
     * @var array<SubcommandGroup|Subcommand|Option>
     */
    public array $options {
        get {
            //Get command options from memory
            $options = $this->getAttribute('options', default: []);

            switch (true) {
                case $this->isGrouped:
                    $subcommandGroup = $this->reflector->getAttribute(SubcommandGroup::class);
                    $subcommandGroup->reflector = $this->reflector;
                    $options[] = $subcommandGroup;
                    break;
                case !empty($this->subCommands):
                    foreach ($this->subCommands as $subCommand) {
                        $options[] = $subCommand;
                    }
                    break;
                default:
                    foreach ($this->handler->method->getParameters() as $parameter) {
                        if ($parameter->hasAttribute(Option::class)) {
                            /** @var Option $option */
                            $option = $parameter->getAttribute(Option::class);
                            $option->reflector = $parameter;
                            $options[] = $option;
                        }
                    }
            }

            $this->setAttribute('options', $options);

            return $options;
        }
    }

    /**
     * The CommandBuilder
     *
     * This CommandBuilder allows us to build the command and send it to discord
     */
    public CommandBuilder $builder {
        get {
            $builder = CommandBuilder::new()
                ->setName($this->name)
                ->setNsfw($this->isNsfw)
                ->setDmPermission($this->directMessage)
                ->setType($this->type)
                ->setDescription($this->description);


            foreach ($this->options as $option) {
                $builder->addOption($option->build);
            }

            return $builder;
        }
    }


    public function __construct(
        string|BackedEnum|null         $name = null,
        public ?string                 $description = null,
        public ?int                    $guildId = null,
        public bool                    $isNsfw = false,
        public array                   $permissions = [],
        public bool                    $directMessage = true,
        public ApplicationCommandTypes $type = ApplicationCommandTypes::CHAT_INPUT,
    )
    {
        $this->setAttribute('name', $name);

        if (($this->type === ApplicationCommandTypes::CHAT_INPUT) && !$this->description) {
            throw new \InvalidArgumentException("Description for command is required when type=CHAT_INPUT");
        }
    }

    public function useReflector(ClassReflector $class): Command
    {
        $this->reflector = $class;
        return $this;
    }
}