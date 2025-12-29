<?php

namespace Tempcord\Attributes\Commands;

use Attribute;
use BackedEnum;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use Tempcord\Support\Traits\HasAttributes;
use Tempest\Reflection\MethodReflector;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_METHOD)]
final class Subcommand
{
    use HasAttributes;

    public ?MethodReflector $reflector = null;

    public string $name {
        get {
            return $this->rememberAttribute('name', fn() => str($this->reflector->getName())
                ->snake('_')
                ->lower()
                ->toString());
        }
    }

    /**
     * @var array<string, Option>
     */
    public array $options {
        get {
            $options = [];
            foreach ($this->reflector->getParameters() as $parameter) {
                if ($parameter->hasAttribute(Option::class)) {
                    /** @var Option $option */
                    $option = $parameter->getAttribute(Option::class);
                    $option->reflector = $parameter;
                    $options[$parameter->getName()] = $option;
                }
            }
            return $options;
        }
    }

    public CommandOptionBuilder $builder {
        get {
            $subcommand = new CommandOptionBuilder()
                ->setName($this->name)
                ->setDescription($this->description)
                ->setType(ApplicationCommandOptionType::SUB_COMMAND);

            foreach ($this->options as $option) {
                $subcommand->addOption($option->build);
            }

            return $subcommand;
        }
    }

    public function __construct(
        public string          $description,
        string|BackedEnum|null $name = null,
    )
    {
        $this->setAttribute('name', $name);
    }

    public function withReflector(MethodReflector $reflector): Subcommand
    {
        $this->reflector = $reflector;
        return $this;
    }
}