<?php

namespace Tempcord\Attributes\Commands;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use Tempcord\Attributes\HasSubcommands;
use Tempcord\Contract\Buildable;
use Tempcord\Contract\CanBeHandled;
use Tempcord\Support\Commands\CommandHandler;
use Tempcord\Support\Traits\HasAttributes;
use Tempest\Reflection\ClassReflector;
use function Tempest\Support\arr;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_CLASS)]
final class Command implements Buildable, CanBeHandled
{
    use HasAttributes, HasSubcommands;

    public ClassReflector $reflector;

    /**
     * Command name
     *
     * If none is provided from constructor - name would be created from the class name,
     * removing "Command" from the beginning and end of the class name.
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
     * Command's handler
     *
     * @var CommandHandler
     */
    public CommandHandler $handler {
        get {
            $method = null;

            $publicMethods = arr($this->reflector->getPublicMethods());
            if ($publicMethods->count() === 1 && $publicMethods->first()->getName() !== '__construct') {
                $method = $this->reflector->getPublicMethods()[0];
            }

            if ($this->reflector->getReflection()->hasMethod('__invoke')) {
                $method = $this->reflector->getMethod('__invoke');
            }

            return new CommandHandler($this, $method);
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
     * @var array<Option|Subcommand>
     */
    public array $options {
        get {
            $options = [];

            if ($this->hasSubcommands) {
                $options = $this->subcommands;
            } else {
                foreach ($this->handler->method?->getParameters() as $parameter) {
                    if ($parameter->hasAttribute(Option::class)) {
                        $options[] = $parameter->getAttribute(Option::class)
                            ?->withReflector($parameter);
                    }
                }
            }

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
                $builder->addOption($option->builder);
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

        if (($this->type === ApplicationCommandTypes::CHAT_INPUT) && $this->description === null) {
            throw new \InvalidArgumentException("Description for command is required when type=CHAT_INPUT");
        }
    }

    public function withReflector(ClassReflector $class): Command
    {
        $this->reflector = $class;
        return $this;
    }
}