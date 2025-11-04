<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandTypes;
use Ragnarok\Fenrir\Exceptions\Rest\Helpers\Command\InvalidCommandNameException;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use RuntimeException;
use Tempcord\Traits\HasAttributes;
use Tempest\Reflection\ClassReflector;
use function Tempest\Support\Arr\dot;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Command
{
    use HasAttributes;

    /**
     * Computed getter for command name
     *
     * If none command is provided - it will take name from the classname
     *
     * @var string
     */
    public string $name {
        get {
            if ($this->hasAttribute('name')) {
                return $this->getAttribute('name') instanceof BackedEnum ? $this->getAttribute('name')->value : $this->getAttribute('name');
            }

            $commandName = str($this->reflector->getShortName())
                ->replaceEnd('Command', '')
                ->replaceStart('Command', '')
                ->snake('_')
                ->lower();

            return $commandName->toString();
        }
    }

    public array $handlers {
        get {
            $keys[$this->name] = [];
            foreach ($this->options as $option) {
                if (in_array(get_class($option), [SubcommandGroup::class, Subcommand::class])) {
                    $keys[$this->name][$option->name] = $option->key;
                } else {
                    $fakeSubcommand = new Subcommand(name: $option->name, description: $option->description);
                    $fakeSubcommand->reflector = $this->reflector->getMethod('__invoke');
                    $keys[$this->name] = $fakeSubcommand->key;
                }
            }
            return dot($keys);
        }
    }

    /** @var array<SubcommandGroup|Subcommand|Option> */
    public array $options {
        get {
            $options = $this->getAttribute('options');

            if ($this->reflector->hasAttribute(SubcommandGroup::class)) {
                /** @var SubcommandGroup $subcommandGroup */
                $subcommandGroup = $this->reflector->getAttribute(SubcommandGroup::class);
                $subcommandGroup->reflector = $this->reflector;
                $options[$subcommandGroup->name] = $subcommandGroup;
            } else {
                // This is subcommands without a group
                $fakeSubcommandGroup = new SubcommandGroup(name: 'fake', description: 'fake');
                $fakeSubcommandGroup->reflector = $this->reflector;
                foreach ($fakeSubcommandGroup->options as $option) {
                    $options[$option->name] = $option;
                }

                if (empty($options)) {
                    /*
                     * We assume that there is no Subcommands found and this is an invokable command
                     * User should provide the __invoke command, so we actually can read the method options (if there are some)
                     */
                    if (!$this->reflector->getReflection()->hasMethod('__invoke')) {
                        throw new RuntimeException('Class [' . $this->reflector->getName() . '] should declare public sub-commands or have an __invoke method');
                    }

                    $fakeSubcommand = new Subcommand(name: 'fake', description: 'fake');
                    $fakeSubcommand->reflector = $this->reflector->getMethod('__invoke');
                    foreach ($fakeSubcommand->options as $option) {
                        $options[$option->name] = $option;
                    }
                }
            }

            $this->setAttribute('options', $options);

            return $this->getAttribute('options');
        }
        set {
            $this->setAttribute('options', array_merge($this->getAttribute('options') ?? [], $value));
        }
    }

    public ClassReflector $reflector;

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
    }

    public function mergeOptions(Command $command): void
    {
        $this->options = $command->options;
    }

    /**
     * @throws InvalidCommandNameException
     */
    public function build(): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName($this->name)
            ->setNsfw($this->isNsfw)
            ->setDmPermission($this->directMessage)
            ->setType($this->type);

        if ($this->type === ApplicationCommandTypes::CHAT_INPUT) {

            if (!$this->description) {
                throw new \LogicException("Description for command [$this->name] is required when type=CHAT_INPUT");
            }

            $command->setDescription($this->description);
        }


        foreach ($this->options as $option) {
            $command->addOption($option->build);
        }

        return $command;
    }
}