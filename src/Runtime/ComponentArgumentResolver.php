<?php

namespace Tempcord\Runtime;

use BackedEnum;
use CyberWolf\Discord\Discord;
use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use CyberWolf\Discord\Interaction\ButtonInteraction;
use CyberWolf\Discord\Interaction\ComponentInteraction;
use CyberWolf\Discord\Interaction\ModalSubmitInteraction;
use InvalidArgumentException;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Enums\ComponentKind;
use Tempest\Reflection\ParameterReflector;

/**
 * Builds the argument list for a component handler.
 *
 * A parameter is filled by its declared type when that type is one of the
 * interaction wrappers, and otherwise by its name: a {placeholder} from the
 * custom id, what a select menu returned, or the field of that name in a
 * submitted modal.
 */
final readonly class ComponentArgumentResolver
{
    public function __construct(
        private Discord $discord,
    ) {}

    /**
     * @param array<string, string> $parameters placeholder values from the custom id
     * @return list<mixed> ordered to match the handler's signature
     */
    public function resolve(
        ComponentDefinition $definition,
        InteractionCreate $interaction,
        array $parameters,
    ): array {
        $arguments = [];

        foreach ($definition->method->getParameters() as $parameter) {
            $arguments[] = $this->argumentFor($definition, $interaction, $parameters, $parameter);
        }

        return $arguments;
    }

    /**
     * @param array<string, string> $parameters
     */
    private function argumentFor(
        ComponentDefinition $definition,
        InteractionCreate $interaction,
        array $parameters,
        ParameterReflector $parameter,
    ): mixed {
        $wrapper = $this->wrapperFor($interaction, $parameter);

        if ($wrapper !== null) {
            return $wrapper;
        }

        $name = $parameter->getName();

        if (array_key_exists($name, $parameters)) {
            return $this->cast($parameters[$name], $parameter);
        }

        $supplied = $this->fromInteraction($definition->kind, $interaction, $name);

        /*
         * Wrapped, so a select menu nobody picked from still supplies its null
         * to a nullable parameter instead of looking like an absent argument.
         */
        if ($supplied !== null) {
            return $supplied[0];
        }

        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        throw new InvalidArgumentException(
            'Missing required parameter: ' . $name . ' for ' . $definition->label(),
        );
    }

    private function wrapperFor(InteractionCreate $interaction, ParameterReflector $parameter): ?object
    {
        if (!$parameter->getReflection()->hasType()) {
            return null;
        }

        return match ($parameter->getType()->getName()) {
            InteractionCreate::class => $interaction,
            ButtonInteraction::class => new ButtonInteraction($interaction, $this->discord),
            ComponentInteraction::class => new ComponentInteraction($interaction, $this->discord),
            ModalSubmitInteraction::class => new ModalSubmitInteraction($interaction, $this->discord),
            default => null,
        };
    }

    /**
     * What the interaction itself carries under this parameter name: the picks
     * from a select menu, or a field of a submitted modal.
     *
     * Returned wrapped in a single-element array, so supplying null is told
     * apart from carrying nothing under that name at all.
     *
     * @return array{mixed}|null
     */
    private function fromInteraction(ComponentKind $kind, InteractionCreate $interaction, string $name): ?array
    {
        if ($kind === ComponentKind::SelectMenu) {
            $values = $interaction->data->values ?? [];

            return match ($name) {
                'values' => [$values],
                'value' => [$values[0] ?? null],
                default => null,
            };
        }

        if ($kind === ComponentKind::ModalSubmit) {
            $modal = new ModalSubmitInteraction($interaction, $this->discord);

            return $modal->hasValue($name) ? [$modal->getValue($name)] : null;
        }

        return null;
    }

    private function cast(string $value, ParameterReflector $parameter): mixed
    {
        if (!$parameter->getReflection()->hasType()) {
            return $value;
        }

        $type = $parameter->getType();

        if ($type->isEnum() && is_subclass_of($type->getName(), BackedEnum::class)) {
            return $type->getName()::from($value);
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }
}
