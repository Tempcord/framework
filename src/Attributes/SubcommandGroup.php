<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use Tempcord\Traits\HasAttributes;
use Tempest\Reflection\ClassReflector;
use function Tempest\Support\Arr\dot;

#[Attribute(Attribute::TARGET_CLASS)]
final  class SubcommandGroup
{
    use HasAttributes;

    public string $name {
        get {
            $name = $this->getAttribute('name');
            return $name instanceof BackedEnum ? $name->value : $name;
        }
    }

    public array $key {
        get {
            $keys = [];
            foreach ($this->options as $option) {
                $keys[$option->name] = $option->key;
            }
            return $keys;
        }
    }

    public ClassReflector $reflector;

    public CommandOptionBuilder $build {
        get {
            $subcommandGroup = CommandOptionBuilder::new()
                ->setName($this->name)
                ->setDescription($this->description)
                ->setType(ApplicationCommandOptionType::SUB_COMMAND_GROUP);

            foreach ($this->options as $option) {
                $subcommandGroup->addOption($option->build);
            }

            return $subcommandGroup;
        }
    }

    /** @var array<string, Subcommand> */
    public array $options {
        get {
            $options = [];
            foreach ($this->reflector->getPublicMethods() as $method) {
                if ($method->hasAttribute(Subcommand::class)) {
                    /** @var Subcommand $subcommand */
                    $subcommand = $method->getAttribute(Subcommand::class);
                    $subcommand->reflector = $method;
                    $options[$subcommand->name] = $subcommand;
                }
            }
            return $options;
        }
    }


    public function __construct(
        string|BackedEnum $name,
        public string     $description
    )
    {
        $this->setAttribute('name', $name);
    }

}