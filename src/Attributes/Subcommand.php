<?php

namespace Tempcord\Attributes;

use Attribute;
use BackedEnum;
use InvalidArgumentException;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use React\Promise\PromiseInterface;
use Tempcord\Traits\HasAttributes;
use Tempest\Reflection\MethodReflector;
use function React\Async\async;
use function React\Async\await;
use function Tempest\get;

#[Attribute(Attribute::TARGET_METHOD)]
final class Subcommand
{
    use HasAttributes;

    public string $name {
        get {
            $name = $this->getAttribute('name');
            return $name instanceof BackedEnum ? $name->value : $name;
        }
    }
    public MethodReflector $reflector;

    public CommandOptionBuilder $build {
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
    public \Closure $key {
        get {
            return function (CommandInteraction $interaction) {


                $subcommandName = $interaction->getSubCommandName() ? str_replace(':', '.', $interaction->getSubCommandName()) : null;

                $getArgs = async(function () use ($interaction, $subcommandName) {
                    $args = [
                        'interaction' => $interaction
                    ];
                    foreach ($this->options as $option) {
                        $args[$option->name] = $option->mapValue($interaction->getOption(
                            path: $subcommandName ? $subcommandName . '.' . $option->name : $option->name
                        ), $interaction);
                    }
                    return $args;
                });


                $getArgs()->then(function (array $args) {
                    $class = get($this->reflector->getDeclaringClass()->getName());
                    $this->invokeNamedArgs($class, $args);
                });

            };
        }
    }

    public function __construct(
        string|BackedEnum $name,
        public string     $description
    )
    {
        $this->setAttribute('name', $name);
    }

    public function invokeNamedArgs(?object $object, array $namedArgs): mixed
    {
        $ordered = [];

        foreach ($this->reflector->getParameters() as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $namedArgs)) {
                $ordered[] = $namedArgs[$name];
            } elseif ($param->isOptional()) {
                $ordered[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Missing required parameter: $name in method {$this->reflector->getShortName()}");
            }
        }

        return $this->reflector->invokeArgs($object, $ordered);
    }
}