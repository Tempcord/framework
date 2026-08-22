<?php

namespace Tempcord\Runtime;

use InvalidArgumentException;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Tempcord\Definitions\HandlerDefinition;
use Throwable;

/**
 * Builds the argument list for a handler out of an interaction.
 *
 * Values are keyed by the parameter they will be passed to rather than by the
 * name Discord knows the option under, so an option renamed with
 * #[Option(name: ...)] still reaches its parameter.
 */
final readonly class ArgumentResolver
{
    /**
     * The parameter name a handler may declare to receive the interaction itself.
     */
    private const string INTERACTION_PARAMETER = 'interaction';

    public function __construct(
        private OptionValueResolver $values,
    ) {}

    /**
     * @return list<mixed> ordered to match the handler's signature
     * @throws Throwable
     */
    public function resolve(HandlerDefinition $handler, CommandInteraction $interaction): array
    {
        $supplied = [self::INTERACTION_PARAMETER => $interaction];

        foreach ($handler->options as $option) {
            $structure = $interaction->getOption($handler->pathTo($option));

            /*
             * An option the user left out is not supplied at all, so the
             * parameter's own default applies. Passing null instead would fail
             * on any parameter that is not nullable.
             */
            if ($structure === null) {
                continue;
            }

            $supplied[$option->parameter->getName()] = $this->values->resolve($structure, $interaction);
        }

        $arguments = [];

        foreach ($handler->method->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $supplied)) {
                $arguments[] = $supplied[$name];
                continue;
            }

            if ($parameter->isOptional()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new InvalidArgumentException(
                "Missing required parameter: {$name} for command \"{$handler->path}\"",
            );
        }

        return $arguments;
    }
}
