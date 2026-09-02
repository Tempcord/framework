<?php

namespace Tempcord\Definitions;

use Tempcord\Discord\Enums\ApplicationCommandOptionType;
use Tempcord\Discord\Enums\ChannelType;
use RuntimeException;
use Tempest\Reflection\MethodReflector;
use Tempest\Reflection\ParameterReflector;

/**
 * A single user-supplied argument to a command or subcommand, resolved from a
 * #[Option] attribute and the parameter it sits on.
 */
final readonly class OptionDefinition
{
    /**
     * @param array<string, string|int|float> $choices keyed by the label users see
     * @param list<ChannelType> $channelTypes
     * @param array<string, string> $nameLocalizations keyed by Discord locale
     * @param array<string, string> $descriptionLocalizations keyed by Discord locale
     */
    public function __construct(
        public string $name,
        public string $description,
        public ApplicationCommandOptionType $type,
        public bool $isRequired,
        public ?AutocompleteDefinition $autocomplete,
        public MethodReflector $method,
        public string $parameterName,
        public array $choices = [],
        public int|float|null $minValue = null,
        public int|float|null $maxValue = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public array $channelTypes = [],
        public array $nameLocalizations = [],
        public array $descriptionLocalizations = [],
    ) {}

    public function hasAutocomplete(): bool
    {
        return $this->autocomplete !== null;
    }

    /**
     * The handler parameter this option feeds.
     *
     * Held as the method and a name rather than as a ParameterReflector,
     * because discovery is cached by exporting it as PHP and ReflectionParameter
     * cannot be written in a form that constructs again. A definition carrying
     * one takes the whole cache down with it, silently: the application then
     * rediscovers everything on every boot.
     */
    public function parameter(): ParameterReflector
    {
        foreach ($this->method->getParameters() as $parameter) {
            if ($parameter->getName() === $this->parameterName) {
                return $parameter;
            }
        }

        throw new RuntimeException(
            'Option [' . $this->name . '] names the parameter $' . $this->parameterName
            . ', which ' . $this->method->getDeclaringClass()->getName() . '::'
            . $this->method->getName() . '() does not have.',
        );
    }
}
