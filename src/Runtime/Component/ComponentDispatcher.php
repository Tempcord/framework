<?php

namespace Tempcord\Runtime\Component;

use Tempcord\Discord\Discord;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Compile\Component\ComponentDefinition;
use Tempcord\Compile\Component\ComponentKind;
use Tempest\Container\Container;
use Tempest\Log\Logger;
use Tempcord\Runtime\Concerns\ContainsFailures;
use Tempcord\Runtime\Middleware\MiddlewarePipeline;

/**
 * Invokes the method behind a component handler, with the class it lives on
 * resolved from the container so a handler may take dependencies of its own.
 */
final readonly class ComponentDispatcher
{
    use ContainsFailures;

    public function __construct(
        private ComponentArgumentResolver $arguments,
        private Container $container,
        private Logger $logger,
        private MiddlewarePipeline $middleware,
        private Discord $discord,
    ) {}

    /**
     * @param array<string, string> $parameters
     */
    public function dispatch(
        ComponentDefinition $definition,
        InteractionCreate $interaction,
        array $parameters,
    ): void {
        $this->contained(
            'Handler for ' . $definition->label(),
            function () use ($definition, $interaction, $parameters): void {
                $this->middleware->run(
                    $definition->middleware,
                    $this->wrap($definition->kind, $interaction),
                    function () use ($definition, $interaction, $parameters): void {
                        $definition->method->invokeArgs(
                            $this->container->get($definition->handler),
                            $this->arguments->resolve($definition, $interaction, $parameters),
                        );
                    },
                );
            },
        );
    }

    /**
     * The interaction in the shape this kind of component is answered with, so
     * a middleware can reply without knowing which pipeline it was reached
     * through.
     *
     * Built here rather than taken from the handler's arguments: a middleware
     * that refuses must be able to say so even when the handler it is guarding
     * never asked for an interaction at all.
     */
    private function wrap(
        ComponentKind $kind,
        InteractionCreate $interaction,
    ): ButtonInteraction|ComponentInteraction|ModalSubmitInteraction {
        return match ($kind) {
            ComponentKind::Button => new ButtonInteraction($interaction, $this->discord),
            ComponentKind::SelectMenu => new ComponentInteraction($interaction, $this->discord),
            ComponentKind::ModalSubmit => new ModalSubmitInteraction($interaction, $this->discord),
        };
    }
}
