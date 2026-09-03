<?php

namespace Tempcord\Runtime;

use InvalidArgumentException;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempest\Reflection\ParameterReflector;
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
        private TargetResolver $targets = new TargetResolver(),
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

            $supplied[$option->parameterName] = $this->values->resolve(
                $structure,
                $interaction,
                $option->parameter(),
            );
        }

        $arguments = [];

        foreach ($handler->method->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $supplied)) {
                $arguments[] = $supplied[$name];
                continue;
            }

            /*
             * The interaction itself, asked for by type rather than by the one
             * name this resolver used to recognise. A component handler has
             * always been able to take either shape of it this way, and a
             * command handler wanting the raw gateway event — for the member
             * who ran it, say — had no way to say so: it failed at the moment
             * somebody used the command, with nothing at discovery to warn
             * that half the commands could not be called.
             */
            $wrapper = $this->wrapperFor($interaction, $parameter);

            if ($wrapper !== null) {
                $arguments[] = $wrapper;
                continue;
            }

            /*
             * A context menu has no options at all: what it was used on
             * arrives beside them, and the parameter's type says which shape
             * of it the handler wants.
             */
            $target = $this->targets->resolve($interaction, $parameter);

            if ($target !== null) {
                $arguments[] = $target;
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

    /**
     * The interaction in whichever shape the parameter is typed for.
     */
    private function wrapperFor(CommandInteraction $interaction, ParameterReflector $parameter): ?object
    {
        if (!$parameter->getReflection()->hasType()) {
            return null;
        }

        return match ($parameter->getType()->getName()) {
            CommandInteraction::class => $interaction,
            InteractionCreate::class => $interaction->interaction,
            default => null,
        };
    }
}
