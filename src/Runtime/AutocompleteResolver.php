<?php

namespace Tempcord\Runtime;

use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Definitions\AutocompleteDefinition;
use Tempest\Container\Container;
use Tempest\Reflection\ParameterReflector;

/**
 * Runs an option's autocomplete, whichever of the three shapes it takes.
 *
 * The container only exists by the time an interaction arrives, which is why
 * this is runtime rather than part of the compiled definition: a class named in
 * an attribute cannot be built while attributes are still being read.
 */
final readonly class AutocompleteResolver
{
    public function __construct(
        private Container $container,
    ) {}

    public function suggest(
        AutocompleteDefinition $autocomplete,
        CommandInteraction $interaction,
        mixed $value,
    ): mixed {
        if ($autocomplete->method !== null) {
            return $autocomplete->method->invokeArgs(
                $this->container->get($autocomplete->method->getDeclaringClass()->getName()),
                $this->argumentsFor($autocomplete, $interaction, $value),
            );
        }

        $instance = $autocomplete->instance
            ?? $this->container->get($autocomplete->className);

        return $instance->handle($interaction, $value);
    }

    /**
     * A completing method takes whatever it asks for, in whatever order: the
     * interaction where it is typed as one, and what has been typed so far
     * everywhere else.
     *
     * @return list<mixed>
     */
    private function argumentsFor(
        AutocompleteDefinition $autocomplete,
        CommandInteraction $interaction,
        mixed $value,
    ): array {
        $arguments = [];

        foreach ($autocomplete->method->getParameters() as $parameter) {
            $arguments[] = $this->isInteraction($parameter) ? $interaction : $value;
        }

        return $arguments;
    }

    private function isInteraction(ParameterReflector $parameter): bool
    {
        return $parameter->getReflection()->hasType()
            && $parameter->getType()->getName() === CommandInteraction::class;
    }
}
